<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filter extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql_auth';

    protected $guarded = [];

    protected $casts = [
        'details' => 'array'
    ];

    protected $appends = [
        'count'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getCountAttribute()
    {
        return $this->details['ids'] ? collect(explode(",", $this->details['ids']))->count() : 0;
    }
}
