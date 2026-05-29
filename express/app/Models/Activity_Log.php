<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity_Log extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = ['user_id','action','details'];
    protected $table = 'activity_logs';
}
