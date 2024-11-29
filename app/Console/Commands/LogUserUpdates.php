<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogUserUpdates extends Command
{
    protected $signature = 'user:log-updates';
    protected $description = 'Logs user updates from batch input';

    public function handle()
    {
        $changes =  $this->getExampleData();

        if (!$changes) {
            $this->error("Failed to load input data.");
            return;
        }

        $validator = Validator::make($changes, [
            'batches' => 'required|array',
            'batches.*.subscribers' => 'required|array',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid input format: ' . json_encode($validator->errors()->all()));
            return;
        }

        $this->logUserUpdates($changes);
    }

    protected function logUserUpdates(array $changes)
    {
        foreach ($changes['batches'] as $batchIndex => $batch) {
            foreach ($batch['subscribers'] as $subscriberIndex => $subscriber) {
                $updates = collect($subscriber)->except('email')->map(fn($value, $key) => "$key: '$value'")->join(', ');
                $logIndex = $batchIndex * 1000 + $subscriberIndex;

                // Log to file and console
                Log::info("[$logIndex] $updates");
                $this->info("[$logIndex] $updates");
            }
        }
    }

    private function getExampleData()
    {
        return [
            "batches" => [
                [
                    "subscribers" => [
                        [
                            "email" => "alex@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen@acme.com",
                            "name" => "Hellen",
                            "time_zone" => "America/Los_Angeles"
                        ],
                        [
                            "email" => "alex1@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen1@acme.com",
                            "name" => "Hellen1",
                            "time_zone" => "America/Los_Angeles"
                        ],
                        [
                            "email" => "alex2@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen2@acme.com",
                            "name" => "Hellen2",
                            "time_zone" => "America/Los_Angeles"
                        ],
                        [
                            "email" => "alex3@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen4@acme.com",
                            "name" => "Hellen4",
                            "time_zone" => "America/Los_Angeles"
                        ],
                        [
                            "email" => "alex5@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen5@acme.com",
                            "name" => "Hellen5",
                            "time_zone" => "America/Los_Angeles"
                        ],
                        [
                            "email" => "alex6@acme.com",
                            "time_zone" => "Europe/Amsterdam"
                        ],
                        [
                            "email" => "hellen6@acme.com",
                            "name" => "Hellen6",
                            "time_zone" => "America/Los_Angeles"
                        ],
                    ]
                ]
            ]
        ];
    }
}
