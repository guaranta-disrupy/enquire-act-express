<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record_Raw extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'records_raws';
    protected $guarded = [];
    protected $casts = [
        'details' => 'json',
        'deleted_at' => 'datetime'
    ];
}
