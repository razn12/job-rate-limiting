<?php

namespace App\Jobs;

use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessBatchJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;
    public $tries = 5; // Total retry attempts
    public function __construct(protected int $batchIndex, protected Collection $subscribers) {}

    public function middleware(): array
    {
        return [
            new RateLimited('batch-api'),
            (new ThrottlesExceptions(10, 5* 60)) // If max exception reach to 10 then delay 5min before retrying
                ->backoff(2) // back off 2 sec for next attempt
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $apiEndpoint = route('batch-api');
        $response = Http::get($apiEndpoint, [
            'batch_index' => $this->batchIndex,
            'subscribers' => $this->subscribers->toArray()
        ]);
        if ($response->failed()) {
            Log::error('Bulk API request failed', [
                'subscriber' => $this->subscribers,
                'response' => $response->json(),
            ]);

            if ($response->status() === 429) { // Rate limit
                $this->release(5*60); // Requeue after 5 minutes
            }
        } else {
            Log::info('Subscriber updated successfully for batch ' . $this->batchIndex);
        }
    }

    public function retryUntil(): DateTime //retry valid until 30 minutes
    {
        return now()->addMinutes(30);
    }

    public function failed(Exception $exception)
    {
        Log::error("Job Failed for Batch: $this->batchIndex", [
            'job' => self::class,
        ]);
    }
}
