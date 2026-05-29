<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectToken extends Model
{
    use HasFactory;

    protected $connection = 'mysql_auth';

    protected $fillable = [
        'token',
        'user_id',
        'component_id',
        'used'
    ];

    protected $table = 'redirect_tokens';

    protected $casts = [
        'used' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
