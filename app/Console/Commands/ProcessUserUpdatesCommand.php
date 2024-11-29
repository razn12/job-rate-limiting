<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBatchJob;
use App\Jobs\ProcessSingleApiJob;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class ProcessUserUpdates
 *
 * # Introduction
 * We use a third-party API that has the following limits:
 * You can make up to 50 requests per hour for batch endpoints and 3,600 individual requests per hour for other API endpoints.
 * Each batch request accommodates up to 1,000 records in the payload for a total of 50,000 updates per hour.
 * We want to keep the user attributes up to date with the provider. We only need to make calls for the user whose attributes are changing.
 * This is about 40000 calls per hour.
 *
 * api response, error code, queue monitoring
 *
 * The batch api accepts this array of changes. The `email` is used as the key.
 *
 * ```jsx
 *  {
 *      "batches": [{
 *          "subscribers": [{
 *               "email": "alex@acme.com",
 *                "time_zone": "Europe/Amsterdam"
 *          },
 *          {
 *              "email": "hellen@acme.com",
 *              "name": "Hellen",
 *              "time_zone": "America/Los_Angeles",
 *          }]
 *      }]
 * }
 * ```
 *
 * ## 1. Implement the feature
 * Instead of making an actual API call, log a message with the update. For example: `[34] firstname: Helen, timezone: 'America/Los_Angeles'`
 */
class ProcessUserUpdatesCommand extends Command
{
    use WithFaker;
    protected $signature   = 'user:process-updates';
    protected $description = 'Process user updates with rate limiting';

    protected $batchLimit = 50000;
    protected $singleApiLimit = 3600;

    public function handle()
    {
        $changes = $this->getExampleData();
        $subscribers =collect($changes['batches'][0]['subscribers']);
        // total request handle batch api 50,000 and single api 3,600 = 53,600
        // Step 1: Process first 50,000 records in batches
        $batches = $subscribers->take($this->batchLimit)->chunk(1000);
        $this->handleBatchApi($batches);

        // Step 2: Process remaining records individually (single api to process 3600)
        $singleApi = $subscribers->skip($this->batchLimit)->take($this->singleApiLimit);
        if ($singleApi->count() > 0) {
            $this->handleSingleApi($singleApi);
        }

    }

    private function handleBatchApi(Collection $batches)
    {
        foreach ($batches as $index => $batch) {

            // Dispatch the job
            ProcessBatchJob::dispatch($index, $batch);

            $this->info("Batch $index dispatched successfully.");
        }

    }

    private function handleSingleApi(Collection $subscribers)
    {
        foreach ($subscribers as $index => $subscriber) {
            // memory management OK

            // Dispatch the job
            ProcessSingleApiJob::dispatch($index, collect($subscriber));

            $this->info("Batch $index dispatched successfully.");
        }

    }


    private function getExampleData()
    {
        return [
            "batches" => [
                [
                    "subscribers" => array_map(function ($index) {
                        // Generate a unique email using the index
                        return [
                            "email" => "email_{$index}@example.com",  // Generate unique email using index
                            "time_zone" => "America/New_York",
                        ];
                    }, range(0, 59999)), // Creates 40,000 unique emails
                ],
            ],
        ];
    }
}
