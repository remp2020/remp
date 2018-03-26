<?php

namespace App\Console\Commands;

use App\Contracts\JournalContract;
use App\Contracts\JournalListRequest;
use App\SessionDevice;
use App\SessionReferer;
use DeviceDetector\DeviceDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Snowplow\RefererParser\Parser;

class ProcessPageviewSessions extends Command
{
    protected $signature = 'pageviews:process-sessions {--now=}';

    protected $description = 'Reads and parses session referers tracked within Beam';

    public function handle(
        JournalContract $journalContract,
        DeviceDetector $deviceDetector,
        Parser $refererParser,
        \League\Uri\Parser $uriParser
    ) {
        $request = new JournalListRequest('pageviews');

        $now = $this->hasOption('now') ? Carbon::parse($this->option('now')) : Carbon::now();
        $timeBefore = $now->minute(0)->second(0);
        $timeAfter = (clone $timeBefore)->subHour();

        // select first pageview of each session
        $request->addSelect("first(token)", "referer", "url", "ref_source", "social", "user_agent");
        $request->setTimeAfter($timeAfter);
        $request->setTimeBefore($timeBefore);
        $request->addGroup("remp_session_id", "subscriber");

        $this->line(sprintf('Fetching pageviews made from <info>%s</info> to <info>%s</info>', $timeAfter, $timeBefore));
        $pageviews = $journalContract->list($request);

        $deviceAggregate = [];
        $deviceBlueprint = [
            'subscriber' => null,
            'os_name' => null,
            'os_version' => null,
            'client_type' => null,
            'client_name' => null,
            'client_version' => null,
            'model' => null,
            'brand' => null,
            'type' => null,
        ];

        $refererAggregate = [];
        $refererBlueprint = [
            'subscriber' => null,
            'medium' => null,
            'source' => null,
        ];

        $bar = $this->output->createProgressBar(count($pageviews));
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $bar->setMessage('Detecting devices and sources');

        foreach ($pageviews as $record) {
            foreach ($record->pageviews as $pageview) {
                $deviceDetector->setUserAgent($pageview->user->user_agent);
                $deviceDetector->parse();

                $client = $deviceDetector->getClient();
                $os = $deviceDetector->getOs();

                $deviceData['subscriber'] = boolval($record->tags->subscriber);
                $deviceData['os_name'] = $os['name'] ?? null;
                $deviceData['os_version'] = $os['version'] ?? null;
                $deviceData['client_type'] = $client['type'] ?? null;
                $deviceData['client_name'] = $client['name'] ?? null;
                $deviceData['client_version'] = $client['version'] ?? null;
                $deviceData['model'] = $deviceDetector->getModel();
                $deviceData['brand'] = $deviceDetector->getBrandName();
                $deviceData['type'] = $deviceDetector->getDeviceName();

                $deviceAggregate = $this->increment($deviceAggregate, $deviceData);

                $refererData = $refererBlueprint;
                if (isset($pageview->user->referer)) {
                    $referer = $refererParser->parse($pageview->user->referer, $pageview->user->url);
                    $refererData['medium'] = $referer->getMedium();
                    $refererData['source'] = $referer->getSource();
                } else {
                    $refererData['medium'] = 'direct';
                }
                if ($refererData['medium'] === 'invalid') {
                    continue;
                }
                if ($refererData['medium'] === 'unknown') {
                    $uri = $uriParser($pageview->user->referer);
                    $refererData['medium'] = 'external';
                    $refererData['source'] = $uri['host'];
                }

                $refererData['subscriber'] = boolval($record->tags->subscriber);
                $refererAggregate = $this->increment($refererAggregate, $refererData);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
        unset($pageviews);

        $deviceConditionsAndCounts = $this->conditionAndCounts($deviceAggregate, $deviceBlueprint);
        if (count($deviceConditionsAndCounts) > 0) {
            $bar = $this->output->createProgressBar(count($deviceConditionsAndCounts));
            $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
            $bar->setMessage('Storing device data');

            foreach ($deviceConditionsAndCounts as $device) {
                $attributes = $device['conditions'];
                $attributes['time_from'] = $timeAfter;
                $attributes['time_to'] = $timeBefore;

                /** @var SessionDevice $sessionDevice */
                $sessionDevice = SessionDevice::firstOrNew($attributes);
                $sessionDevice->count = $device['count'];
                $sessionDevice->save();

                $bar->advance();
            }
            $bar->finish();
            $this->line('');
            unset($deviceConditionsAndCounts);
        }

        $refererConditionsAndCounts = $this->conditionAndCounts($refererAggregate, $refererBlueprint);
        if (count($refererConditionsAndCounts) > 0) {
            $bar = $this->output->createProgressBar(count($refererConditionsAndCounts));
            $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
            $bar->setMessage('Storing device data');

            foreach ($refererConditionsAndCounts as $referer) {
                $attributes = $referer['conditions'];
                $attributes['time_from'] = $timeAfter;
                $attributes['time_to'] = $timeBefore;

                /** @var SessionReferer $sessionReferer */
                $sessionReferer = SessionReferer::firstOrNew($attributes);
                $sessionReferer->count = $referer['count'];
                $sessionReferer->save();

                $bar->advance();
            }
            $bar->finish();
            $this->line('');
            unset($refererConditionsAndCounts);
        }
    }

    private function increment($aggregate, $data)
    {
        if (is_array($aggregate)) {
            reset($aggregate);
            $firstKey = key($data);
        } else {
            $firstKey = null;
        }

        if ($firstKey === null) {
            if (is_int($aggregate)) {
                return $aggregate + 1;
            } else {
                return 1;
            }
        }

        $keyVal = $data[$firstKey];
        if (!isset($aggregate[$keyVal])) {
            $aggregate[$keyVal] = [];
        }

        unset($data[$firstKey]);
        $aggregate[$keyVal] = $this->increment($aggregate[$keyVal], $data);

        return $aggregate;
    }

    private function conditionAndCounts($aggregate, $data, $conditions = [])
    {
        if (is_array($aggregate)) {
            reset($aggregate);
            $firstKey = key($data);
        } else {
            $firstKey = null;
        }

        if (!$firstKey) {
            return [['conditions' => $conditions, 'count' => $aggregate]];
        }

        $conditionsAndCounts = [];
        foreach ($aggregate as $conditionValue => $conditionAggregate) {
            unset($data[$firstKey]);
            if ($conditionValue === "") {
                $conditionValue = null;
            }
            $result = $this->conditionAndCounts($conditionAggregate, $data, array_merge(
                $conditions,
                [$firstKey => $conditionValue]
            ));
            $conditionsAndCounts = array_merge($conditionsAndCounts, $result);
        }

        return $conditionsAndCounts;
    }
}
