<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateRecordsTable extends Command
{
    protected $signature = 'records:migrate';

    public function handle()
    {
        $this->info('Starting migration of records to records_new...');

        $chunkSize = 200;
        $totalMigrated = 0;

        // Step 1: Migrate data in chunks
        DB::table('records')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use (&$totalMigrated) {
                $data = $records->map(fn($record) => (array) $record)->toArray();
                DB::table('records_new')->insert($data);
                $totalMigrated += count($data);
                $this->info("Migrated {$totalMigrated} records...");
            });

        $this->info("Data migration complete. Total records migrated: {$totalMigrated}");

        // Step 2: Atomically rename tables
        $this->info("Renaming tables...");

        try {
            DB::statement('RENAME TABLE records TO records_old, records_new TO records');
            $this->info("Renaming successful.");
        } catch (\Exception $e) {
            $this->error("Renaming failed: " . $e->getMessage());
            return 1;
        }

        $this->info("Migration and table renaming complete.");
        return 0;
        
    }
}