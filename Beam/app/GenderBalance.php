<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class GenderBalance
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.azure_computer_vision.endpoint'),
            'headers' => [
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => config('services.azure_computer_vision.api_key'),
            ],
        ]);
    }

    public function getGenderCounts($imageUrl): array
    {
        $totalMenCount = 0;
        $totalWomenCount = 0;

        $personCropUrls = $this->identifyPeople($imageUrl);

        foreach ($personCropUrls as $personCropUrl) {
            $isWoman = $this->getPersonGender($personCropUrl);
            if (is_null($isWoman)) {
                continue;
            }
            if ($isWoman) {
                $totalWomenCount++;
            } else {
                $totalMenCount++;
            }
            usleep(5000);//5ms to prevent API usage block
        }

        return [
            'men' => $totalMenCount,
            'women' => $totalWomenCount,
        ];
    }

    private function identifyPeople($imageUrl): array
    {
        $imageUrl = preg_replace('/img.projektn.sk\/wp-static/', 'a-static.projektn.sk', $imageUrl);

        try {
            $response = $this->client->post('computervision/imageanalysis:analyze', [
                'query' => [
                    'api-version' => config('services.azure_computer_vision.api_version'),
                    'gender-neutral-caption' => 'false',
                    'features' => 'people',
                ],
                'json' => [
                    'url' => $imageUrl,
                ]
            ]);
        } catch (ClientException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        $contents = json_decode($response->getBody()->getContents(), false);
        $personCropUrls = [];
        foreach ($contents->peopleResult->values as $boundingObj) {
            // Have at least 60% confidence rectangle contains person (empirical value)
            if ($boundingObj->confidence > 0.6) {
                $x = $boundingObj->boundingBox->x;
                $y = $boundingObj->boundingBox->y;
                $w = $boundingObj->boundingBox->w;
                $h = $boundingObj->boundingBox->h;

                // Ignore bounding boxes smaller than 40x40px
                if ($w <= 40 || $h <= 40) {
                    continue;
                }

                $newImageUrl = preg_replace('/a-static.projektn.sk\//', 'img.projektn.sk/wp-static/', $imageUrl);
                $newImageUrl .= "?crop=$w,$h,$x,$y";
                $personCropUrls[] = $newImageUrl;
            }
        }

        return $personCropUrls;
    }

    private function getPersonGender($imageUrl): ?int
    {
        try {
            $response = $this->client->post('computervision/imageanalysis:analyze', [
                'query' => [
                    'api-version' => config('services.azure_computer_vision.api_version'),
                    'gender-neutral-caption' => 'false',
                    'features' => 'caption,tags',
                ],
                'json' => [
                    'url' => $imageUrl,
                ]
            ]);
        } catch (ClientException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        $c = json_decode($response->getBody()->getContents(), false);

        $tags = $c->tagsResult->values;
        foreach ($tags as $tag) {
            // Tags are sorted by confidence
            if ($tag->confidence < 0.6) {
                break;
            }

            if ($tag->name === 'man') {
                return 0;
            }
            if ($tag->name === 'woman') {
                return 1;
            }
        }

        $caption = $c->captionResult->text;
        if (str_contains($caption, 'a man')) {
            return 0;
        }
        if (str_contains($caption, 'a woman')) {
            return 1;
        }

        return null;
    }
}
