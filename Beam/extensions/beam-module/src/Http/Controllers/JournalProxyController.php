<?php

namespace Remp\BeamModule\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Remp\Journal\JournalException;

class JournalProxyController extends Controller
{
    private $journalClient;

    public function __construct(Client $journalClient)
    {
        $this->journalClient = $journalClient;
    }

    public function pageviewsProgressCount(Request $request)
    {
        return $this->proxyCall($request, '/journal/pageviews/actions/progress/count');
    }

    public function pageviewsUniqueBrowsersCount(Request $request)
    {
        return $this->proxyCall($request, '/journal/pageviews/actions/load/unique/browsers');
    }

    public function commercePurchaseCount(Request $request)
    {
        return $this->proxyCall($request, '/journal/commerce/steps/purchase/count');
    }

    private function proxyCall(Request $request, string $journalUri)
    {
        $requestJson = $request->json()->all();

        try {
            $segmentsResponse = $this->journalClient->post($journalUri, [
                'json' => $requestJson
            ]);
        } catch (ConnectException $e) {
            throw new JournalException("Could not connect to Journal $journalUri endpoint: {$e->getMessage()}");
        }

        return response()->json(json_decode($segmentsResponse->getBody()));
    }
}
