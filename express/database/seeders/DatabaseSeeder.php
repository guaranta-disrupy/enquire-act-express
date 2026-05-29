<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * php artisan db:seed --class=DatabaseSeeder
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            QuestionTypeSeeder::class,
            ThemeSeeder::class,
            QuestionTemplateLanguageSeeder::class
        ]);
    }
}
