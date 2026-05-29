<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTemplateLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=QuestionTemplateLanguageSeeder
     */
    public function run(): void
    {
        DB::table("question_template_languages")->insert([
            [
                "id" => 1,
                "name" => "English"
            ],
            [
                "id" => 2,
                "name" => "Portuguese"
            ],
            [
                "id" => 3,
                "name" => "French"
            ],
            [
                "id" => 4,
                "name" => "German"
            ],
            [
                "id" => 5,
                "name" => "Dutch"
            ],
            [
                "id" => 6,
                "name" => "Greek"
            ],
            [
                "id" => 7,
                "name" => "Romania"
            ],
            [
                "id" => 8,
                "name" => "Serbia"
            ],
            [
                "id" => 9,
                "name" => "Chinese"
            ],
            [
                "id" => 10,
                "name" => "Spanish"
            ],
            [
                "id" => 11,
                "name" => "Bahasa"
            ],
            [
                "id" => 12,
                "name" => "Arabic"
            ],
            [
                "id" => 13,
                "name" => "Russian"
            ]
        ]);
    }
}
