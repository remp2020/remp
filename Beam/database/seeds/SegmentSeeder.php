<?php

use Illuminate\Database\Seeder;

class SegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->getOutput()->writeln('Generating segments...');

        foreach ($this->industrySegments() as $blueprint) {
            if (!\App\Segment::where(['code' => $blueprint['code']])->exists()) {
                $rules = $blueprint['rulesDef'];
                unset($blueprint['rulesDef']);
                $segment = \App\Segment::create($blueprint);
                foreach ($rules as $rule) {
                    $segment->rules()->create($rule);
                }
                $this->command->getOutput()->writeln(" * Segment <info>{$blueprint['code']}</info> created");
            } else {
                $this->command->getOutput()->writeln(" * Segment <info>{$blueprint['code']}</info> exists");
            }
        }
    }

    public function industrySegments()
    {
        return [
            // all pageviews

            [
                'name' => 'First pageview in 30 days',
                'code' => 'first-pageview-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 30*24*60,
                        'count' => 1,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => 'First pageview in 90 days',
                'code' => 'first-pageview-in-90-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 1,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => '2-5 pageviews in 30 days',
                'code' => '2-5-pageviews-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 2,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => [],
                    ],
                    [
                        'timespan' => 90*24*60,
                        'count' => 5,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '<=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => '6-10 pageviews in 30 days',
                'code' => '6-10-pageviews-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 6,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => [],
                    ],
                    [
                        'timespan' => 90*24*60,
                        'count' => 10,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '<=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => '11+ pageviews in 30 days',
                'code' => '11-plus-pageviews-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 11,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],

            // article pageviews

            [
                'name' => 'First article view in 30 days',
                'code' => 'first-article-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 1,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => ['_article' => '1'],
                    ],
                ],
            ],
            [
                'name' => '2-3 article views in 30 days',
                'code' => '2-3-article-views-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 2,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => ['_article' => '1'],
                    ],
                    [
                        'timespan' => 90*24*60,
                        'count' => 3,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '<=',
                        'fields' => [],
                        'flags' => ['_article' => '1'],
                    ],
                ],
            ],
            [
                'name' => '4+ article views in 30 days',
                'code' => '4-plus-article-views-in-30-days',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 90*24*60,
                        'count' => 4,
                        'event_category' => 'pageview',
                        'event_action' => 'load',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => ['_article' => '1'],
                    ],
                ],
            ],

            // commerce

            [
                'name' => 'Never seen the checkout',
                'code' => 'never-seen-the-checkout',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 0,
                        'event_category' => 'commerce',
                        'event_action' => 'checkout',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => "Seen checkout once and didn't pay",
                'code' => 'seen-checkout-once-didnt-pay',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 1,
                        'event_category' => 'commerce',
                        'event_action' => 'checkout',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 0,
                        'event_category' => 'commerce',
                        'event_action' => 'purchase',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
            [
                'name' => "Seen checkout 2-5x and didn't pay",
                'code' => 'seen-checkout-2-5-didnt-pay',
                'active' => true,
                'rulesDef' => [
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 1,
                        'event_category' => 'commerce',
                        'event_action' => 'checkout',
                        'operator' => '=',
                        'fields' => [],
                        'flags' => [],
                    ],
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 1,
                        'event_category' => 'commerce',
                        'event_action' => 'purchase',
                        'operator' => '>=',
                        'fields' => [],
                        'flags' => [],
                    ],
                    [
                        'timespan' => 10*365*24*60,
                        'count' => 5,
                        'event_category' => 'commerce',
                        'event_action' => 'purchase',
                        'operator' => '<=',
                        'fields' => [],
                        'flags' => [],
                    ],
                ],
            ],
        ];
    }
}