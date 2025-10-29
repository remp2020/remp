<?php

namespace Remp\CampaignModule\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Remp\CampaignModule\Campaign;

class CampaignEventsPopulatorCommand extends Command
{
    protected $signature = 'campaign:populate-events
                            {--campaign-id= : Specific campaign ID to generate events for}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--events-per-day=1000 : Average number of show events per day}
                            {--dry-run : Show what would be generated without writing}';

    protected $description = 'Generates realistic banner tracking events for testing campaign statistics';

    private const CLICK_RATE_MIN = 2;
    private const CLICK_RATE_MAX = 5;
    private const PURCHASE_RATE_MIN = 5;
    private const PURCHASE_RATE_MAX = 20;
    private const BUSINESS_HOURS_WEIGHT = 70;

    private ?Client $trackerClient = null;
    private ?string $propertyToken = null;

    public function handle(): int
    {
        $campaignId = $this->option('campaign-id');
        $from = $this->option('from') ? Carbon::parse($this->option('from'), 'UTC') : now('UTC')->subDays(365);
        $to = $this->option('to') ? Carbon::parse($this->option('to'), 'UTC') : now('UTC');
        $eventsPerDay = (int)$this->option('events-per-day');
        $isDryRun = $this->option('dry-run');

        $this->line('Campaign Events Generator');
        $this->line("Date range: <info>{$from->toDateString()}</info> to <info>{$to->toDateString()}</info>");
        $this->line("Events per day: <info>~{$eventsPerDay}</info>");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be written');
        }
        $this->line('');

        $campaigns = $campaignId
            ? Campaign::where('id', $campaignId)->get()
            : Campaign::all();

        if ($campaigns->isEmpty()) {
            $this->error('No campaigns found!');
            return 1;
        }

        $variantUuids = $campaigns->flatMap(fn($campaign) => $campaign->campaignBanners->pluck('uuid'))->toArray();

        if (empty($variantUuids)) {
            $this->error('No campaign banner variants found!');
            return 1;
        }

        $this->line('Found <info>' . count($variantUuids) . '</info> campaign banner variants');
        collect($variantUuids)->each(fn($uuid) => $this->line("  - {$uuid}"));
        $this->line('');

        if ($isDryRun) {
            return $this->handleDryRun($from, $to, $eventsPerDay);
        }

        if (!$this->connectToTracker()) {
            return 1;
        }

        [$totalShows, $totalClicks, $totalPurchases] = $this->generateEvents($variantUuids, $from, $to, $eventsPerDay);

        $this->line('Generation complete');
        $this->line('Total shows: <info>' . number_format($totalShows) . '</info>');

        $clickPercentage = $totalShows > 0 ? ' (' . round($totalClicks / $totalShows * 100, 2) . '%)' : '';
        $this->line('Total clicks: <info>' . number_format($totalClicks) . '</info>' . $clickPercentage);

        $purchasePercentage = $totalClicks > 0 ? ' (' . round($totalPurchases / $totalClicks * 100, 2) . '% of clicks)' : '';
        $this->line('Total purchases: <info>' . number_format($totalPurchases) . '</info>' . $purchasePercentage);

        $this->line('');

        $this->refreshCaches();

        $this->aggregateStats($from, $to);

        $this->line('Done! Visit <info>' . route('campaigns.stats', $campaignId ?: $campaigns->first()->id) . '</info> to see the results.');

        return 0;
    }

    private function handleDryRun(Carbon $from, Carbon $to, int $eventsPerDay): int
    {
        $daysDiff = $from->diffInDays($to);
        $estimatedShows = $daysDiff * $eventsPerDay;
        $estimatedClicks = (int)($estimatedShows * 0.035);
        $estimatedPurchases = (int)($estimatedClicks * 0.015);

        $this->line('Estimated generation:');
        $this->line("  Days: <info>{$daysDiff}</info>");
        $this->line("  Shows: <info>~" . number_format($estimatedShows) . "</info>");
        $this->line("  Clicks: <info>~" . number_format($estimatedClicks) . "</info>");
        $this->line("  Purchases: <info>~" . number_format($estimatedPurchases) . "</info>");

        return 0;
    }

    private function connectToTracker(): bool
    {
        $trackerUrl = config('services.remp.beam.tracker_addr');

        if (!$trackerUrl) {
            $this->error('REMP_TRACKER_ADDR is not configured. Please set it in your .env file.');
            return false;
        }

        $this->propertyToken = $this->getPropertyToken();

        if (!$this->propertyToken) {
            $this->error('No property token found. Please seed Beam database first (php artisan db:seed).');
            return false;
        }

        $this->trackerClient = new Client([
            'base_uri' => rtrim($trackerUrl, '/'),
            'timeout' => 30,
            'connect_timeout' => 5,
        ]);

        $this->line("Connected to Tracker at <info>{$trackerUrl}</info>");
        $this->line("Using property token: <info>{$this->propertyToken}</info>");

        return true;
    }

    private function getPropertyToken(): ?string
    {
        try {
            return DB::table('beam.properties')
                ->orderBy('id')
                ->value('uuid');
        } catch (Exception $e) {
            $this->warn('Could not retrieve property token from Beam database: ' . $e->getMessage());
            return null;
        }
    }

    private function generateEvents(array $variantUuids, Carbon $from, Carbon $to, int $eventsPerDay): array
    {
        $this->line('');
        $this->line('Generating events...');

        $totalShows = 0;
        $totalClicks = 0;
        $totalPurchases = 0;

        $currentDate = $from->copy();
        $progressBar = $this->output->createProgressBar($from->diffInDays($to) + 1);

        while ($currentDate->lte($to)) {
            if ($currentDate->isWeekend()) {
                $baseShowsPerDay = rand((int)($eventsPerDay * 0.3), (int)($eventsPerDay * 0.8));
            } else {
                $baseShowsPerDay = rand((int)($eventsPerDay * 0.8), (int)($eventsPerDay * 1.2));
            }

            for ($i = 0; $i < $baseShowsPerDay; $i++) {
                $eventTime = $this->generateRandomTimeForDate($currentDate);
                $variantUuid = $variantUuids[array_rand($variantUuids)];
                $userId = 'user_' . rand(1, 10000);
                $browserId = 'browser_' . rand(1, 5000);

                $this->sendShowEvent($eventTime, $variantUuid, $userId, $browserId);
                $totalShows++;

                if (rand(1, 100) > rand(self::CLICK_RATE_MIN, self::CLICK_RATE_MAX)) {
                    continue;
                }

                $clickTime = $eventTime->copy()->addSeconds(rand(1, 30));
                $this->sendClickEvent($clickTime, $variantUuid, $userId, $browserId);
                $totalClicks++;

                if (rand(1, 1000) > rand(self::PURCHASE_RATE_MIN, self::PURCHASE_RATE_MAX)) {
                    continue;
                }

                $purchaseTime = $clickTime->copy()->addMinutes(rand(1, 60));
                $this->sendPurchaseEvent($purchaseTime, $variantUuid, $userId, $browserId);
                $totalPurchases++;
            }

            $progressBar->advance();
            $currentDate->addDay();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        return [$totalShows, $totalClicks, $totalPurchases];
    }

    private function sendShowEvent(Carbon $time, string $variantUuid, string $userId, string $browserId): void
    {
        $this->sendTrackerEvent('/track/event', [
            'system' => [
                'property_token' => $this->propertyToken,
                'time' => $time->toIso8601String(),
            ],
            'user' => [
                'id' => $userId,
                'browser_id' => $browserId,
                'source' => [
                    'rtm_variant' => $variantUuid,
                ],
            ],
            'category' => 'banner',
            'action' => 'show',
            'remp_event_id' => Str::uuid()->toString(),
        ]);
    }

    private function sendClickEvent(Carbon $time, string $variantUuid, string $userId, string $browserId): void
    {
        $this->sendTrackerEvent('/track/event', [
            'system' => [
                'property_token' => $this->propertyToken,
                'time' => $time->toIso8601String(),
            ],
            'user' => [
                'id' => $userId,
                'browser_id' => $browserId,
                'source' => [
                    'rtm_variant' => $variantUuid,
                ],
            ],
            'category' => 'banner',
            'action' => 'click',
            'remp_event_id' => Str::uuid()->toString(),
        ]);
    }

    private function sendPurchaseEvent(Carbon $time, string $variantUuid, string $userId, string $browserId): void
    {
        $amount = (float)rand(500, 20000) / 100;
        $transactionId = Str::uuid()->toString();

        $this->sendTrackerEvent('/track/commerce', [
            'system' => [
                'property_token' => $this->propertyToken,
                'time' => $time->toIso8601String(),
            ],
            'user' => [
                'id' => $userId,
                'browser_id' => $browserId,
                'source' => [
                    'rtm_variant' => $variantUuid,
                ],
            ],
            'step' => 'purchase',
            'purchase' => [
                'transaction_id' => $transactionId,
                'revenue' => [
                    'amount' => $amount,
                    'currency' => 'EUR',
                ],
                'product_ids' => [],
            ],
            'remp_commerce_id' => $transactionId,
        ]);
    }

    private function sendTrackerEvent(string $endpoint, array $payload): void
    {
        try {
            $this->trackerClient->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (GuzzleException) {
            // Silently ignore tracking errors to avoid stopping batch generation
            // Most likely cause: tracker is processing events too slowly
        }
    }

    private function refreshCaches(): void
    {
        $this->newLine();
        $this->line('Waiting for Elasticsearch to index events...');
        sleep(10);

        $this->newLine();
        $this->line('Open a new terminal on your <info>host machine</info> and run:');
        $this->line('  <comment>docker compose restart beam_segments</comment>');
        $this->newLine();
        $this->line('Press <info>Enter</info> when done...');
        fgets(STDIN);

        $this->line('Refreshing campaign cache...');
        $this->call('campaigns:refresh-cache');
    }

    private function aggregateStats(Carbon $from, Carbon $to): void
    {
        $this->line('');
        $this->line('Aggregating stats from Elasticsearch to MySQL...');
        $this->warn('This may take a few minutes for large date ranges');

        $hourCurrent = $from->copy()->setTime(0, 0);
        $hourEnd = $to->copy()->setTime(0, 0);
        $aggregateBar = $this->output->createProgressBar($hourCurrent->diffInHours($hourEnd));

        while ($hourCurrent->lte($hourEnd)) {
            $this->callSilently('campaigns:aggregate-stats', [
                '--now' => $hourCurrent->toDateTimeString(),
                '--include-inactive' => true
            ]);
            $hourCurrent->addHour();
            $aggregateBar->advance();
        }

        $aggregateBar->finish();
        $this->line('');
        $this->line('');
    }

    private function generateRandomTimeForDate(Carbon $date): Carbon
    {
        $hour = rand(0, 100) < self::BUSINESS_HOURS_WEIGHT ? rand(9, 18) : rand(0, 23);
        $minute = rand(0, 59);
        $second = rand(0, 59);

        return $date->copy()->setTime($hour, $minute, $second);
    }
}
