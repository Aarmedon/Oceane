<?php

namespace App\Jobs;

use App\Services\GameCycleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResolveTurnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $initiatedByUserId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $initiatedByUserId = null)
    {
        $this->initiatedByUserId = $initiatedByUserId;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(GameCycleManager $manager): void
    {
        Log::info('ResolveTurnJob started', ['initiated_by' => $this->initiatedByUserId]);
        $manager->executeTurn();
        Log::info('ResolveTurnJob finished');
    }
}
