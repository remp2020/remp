<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\CampaignSegment;

/** @extends Factory<CampaignSegment> */
class CampaignSegmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\CampaignSegment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'campaign_id' => 1,
            'code' => 'demo_segment',
            'provider' => 'remp_segment',
        ];
    }
}
