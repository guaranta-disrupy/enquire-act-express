<?php

namespace App\Console\Commands;

use App\Models\ConversationMemory;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupOldConversationMemories extends Command
{
    protected $signature = 'conversation-memories:cleanup {--days=1 : Delete summaries older than this many days}';
    protected $description = 'Delete conversation memory summaries older than specified days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $deleted = ConversationMemory::query()
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deleted} conversation memory summaries older than {$days} day(s).");
        
        return 0;
    }
}