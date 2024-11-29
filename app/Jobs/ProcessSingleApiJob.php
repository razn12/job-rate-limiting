<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ProcessSingleApiJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;


    public function __construct(protected int $subscriberIndex, protected Collection $subscriber) {}

    public function middleware(): array
    {
        return [
            new RateLimited('single-api'),
            (new ThrottlesExceptions(10, 5 * 60))->backoff(5) // delay 5min
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Log::info("Batch job $this->subscriberIndex");
        $apiEndpoint = route('single-api');
        $response = Http::get($apiEndpoint, [
            'index' => $this->subscriberIndex,
            'subscriber' => $this->subscriber->toArray()
        ]);
        if ($response->failed()) {
            Log::error('Bulk API request failed', [
                'subscriber' => $this->subscriber,
                'response' => $response->json(),
            ]);

            if ($response->status() === 429) { // Rate limit
                $this->release(5*60); // Requeue after 5 minutes
            }
        } else {
            Log::info('Subscriber updated successfully for single api ' . $this->subscriberIndex);
        }
    }
}
