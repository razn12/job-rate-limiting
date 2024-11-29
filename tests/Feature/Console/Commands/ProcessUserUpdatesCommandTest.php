<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessBatchJob;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessUserUpdatesCommandTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function it_dispatches_jobs_in_batches_and_respects_rate_limit()
    {
        // Fake the job dispatching so, we don't actually dispatch jobs
        Bus::fake();

        RateLimiter::clear('batch-api'); // Clear any previous hits

        // Run the command that processes user updates
        Artisan::call('user:process-updates');
        // Assert that 40 jobs are dispatched
        Bus::assertDispatched(ProcessBatchJob::class, 40);

        Artisan::call('user:process-updates');
        // Assert that no more than 50 jobs were dispatched
        Bus::assertDispatched(ProcessBatchJob::class, 50);

        // Check that the rate limit was hit
        $this->assertTrue(RateLimiter::tooManyAttempts('batch-api', 50));

        // Now simulate a reset of the rate limit (after 1 hour)
        RateLimiter::clear('batch-api');
        Artisan::call('user:process-updates');

        // Assert that the 90 (50 + 40) job is dispatched after the reset
        Bus::assertDispatched(ProcessBatchJob::class, 90);
    }
}
