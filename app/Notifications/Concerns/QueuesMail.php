<?php

namespace App\Notifications\Concerns;

use Illuminate\Bus\Queueable;

trait QueuesMail
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 45;

    public int $maxExceptions = 4;

    public bool $failOnTimeout = true;

    public bool $deleteWhenMissingModels = true;

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => $this->mailQueueName()];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return [
            'mail' => (bool) config('ukapi.operations.queue_worker_enabled')
                ? (string) config('queue.default', 'database')
                : 'sync',
        ];
    }

    protected function mailQueueName(): string
    {
        return (string) config('ukapi.notifications.default_queue', 'mail');
    }
}
