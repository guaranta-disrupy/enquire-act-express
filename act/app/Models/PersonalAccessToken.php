<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    use HasFactory;

    protected $connection = 'mysql_auth';
}
