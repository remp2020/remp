<?php

namespace App\Console;

use App\CrmApi;
use App\GorseApi;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
use Remp\Journal\JournalContract;
use Remp\Journal\ListRequest;

class UploadPageviewsToGorse extends Command
{
    private const COMMAND = 'gorse:upload';
    private const ARTICLE_TIMESPENT_THRESHOLD = 20;

    protected $signature = self::COMMAND . ' {--now=}';

    protected $description = 'Upload pageviews to Gorse Recommendation Engine';

    public function handle(JournalContract $journalContract, GorseApi $gorseApi, CrmApi $crmApi)
    {
        $now = $this->option('now') ? \Illuminate\Support\Carbon::parse($this->option('now')) : Carbon::now();

        $timeBefore = $now->floorMinutes(10);
        $timeAfter = (clone $timeBefore)->subMinutes(10);
        $urlFilter = explode(',', config('services.gorse_recommendation.url_filter'));

        $this->line('');
        $this->line(sprintf("Fetching pageviews and timespent data from <info>%s</info> to <info>%s</info>.", $timeAfter, $timeBefore));

        $r = ListRequest::from('pageviews')
            ->setTimeAfter($timeAfter)
            ->setTimeBefore($timeBefore)
            ->addInverseFilter('url', ...$urlFilter)
            ->setLoadTimespent();

        $events = $journalContract->list($r);
        $filtered = collect($events[0]->pageviews)->filter(function ($event) {
            return isset($event->article);
        });

        $uniqueUserIds = $filtered->map(function ($source) {
            return $source->user->id ?? null;
        })->filter()->unique()->values()->all();

        $userIdToUuidMap = $this->getUserIdToUuidMapping($crmApi, $uniqueUserIds);

        $feedback = [];
        foreach ($filtered as $source) {
            // if there is no mapping to uuid, use browser_id as user_id
            $userId = isset($source->user->id)
                ? ($userIdToUuidMap[$source->user->id] ?? $source->user->browser_id)
                : $source->user->browser_id;

            $feedback[] = [
                'FeedbackType' => 'read',
                'ItemId' => $source->article->id,
                'Timestamp' => $source->system->time,
                'UserId' => $userId,
            ];

            if (isset($source->user->timespent) && $source->user->timespent >= self::ARTICLE_TIMESPENT_THRESHOLD) {
                $feedback[] = [
                    'FeedbackType' => 'timespent',
                    'ItemId' => $source->article->id,
                    'Timestamp' => $source->system->time,
                    'UserId' => $userId,
                ];
            }
        }

        $gorseResponse = $gorseApi->insertFeedback($feedback);

        $this->output->writeln('DONE.');
        $this->output->writeln('RowAffected: ' . $gorseResponse->RowAffected);
    }

    private function getUserIdToUuidMapping(CrmApi $crmApi, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $mapping = [];
        $page = 1;

        do {
            $response = $crmApi->getUsersList($userIds, $page);

            foreach ($response->users ?? [] as $user) {
                $mapping[$user->id] = $user->uuid;
            }
        } while (($response->totalPages ?? 0) > $page++);

        return $mapping;
    }
}
