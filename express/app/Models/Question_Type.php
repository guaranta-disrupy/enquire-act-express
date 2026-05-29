<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question_Type extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'question_types';

    protected $casts = [
        'setup' => 'json',
        'deleted_at' => 'datetime'
    ];
}
