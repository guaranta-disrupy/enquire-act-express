<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationMemory extends Model
{
    use HasFactory;

    // This model persists the rolling summary of a user's conversation memory
    protected $fillable = [
        'user_id',
        'platform',
        'summary',
        'last_summary_at',
        'token_count',
    ];
}


