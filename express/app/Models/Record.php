<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];
    protected $casts = [
        "agent" => "json",
        "url_data" => "json",
        "responses" => "json",
        "history" => "json",
        "config_data" => "json",
        'deleted_at' => 'datetime'
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id', 'id');
    }
}

