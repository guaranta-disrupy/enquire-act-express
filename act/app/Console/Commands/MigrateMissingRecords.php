<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMissingRecords extends Command
{
    protected $signature = 'records:migrate-missing';

    public function handle()
    {
        $this->info('Starting migration of missing records from records_old to records...');

        $chunkSize = 200;
        $totalMigrated = 0;
        $totalSkipped = 0;

        // Migrate data in chunks, only inserting missing records
        DB::table('records_old')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use (&$totalMigrated, &$totalSkipped) {
                $dataToInsert = [];
                
                foreach ($records as $record) {
                    // Check if this record already exists in records
                    $exists = DB::table('records')
                        ->where('id', $record->id)
                        ->exists();
                    
                    if (!$exists) {
                        $dataToInsert[] = (array) $record;
                    } else {
                        $totalSkipped++;
                    }
                }
                
                // Insert only the records that don't exist
                if (!empty($dataToInsert)) {
                    DB::table('records')->insert($dataToInsert);
                    $totalMigrated += count($dataToInsert);
                }
                
                $this->info("Migrated {$totalMigrated} records, skipped {$totalSkipped} existing records...");
            });

        $this->info("Migration complete. Total records migrated: {$totalMigrated}, Total skipped: {$totalSkipped}");
        return 0;
    }
} 