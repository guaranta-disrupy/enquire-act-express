<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearUnusedTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = now()->year;
        $counts = DB::table('personal_access_tokens')->where('name', 'engine')->whereYear('created_at', '<', $year)->count();
        DB::table('personal_access_tokens')->where('name', 'engine')->whereYear('created_at', '<', $year)->delete();
        $this->info($counts . ' records deleted.');
        return Command::SUCCESS;
    }
}
