<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenManager extends Model
{
    use HasFactory;

    protected $table = 'token_manager';

    protected $fillable = [
        'user_id',
        'date',
        'used_tokens',
    ];
}


