<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $appends = [
        'created_readable',
        'updated_readable'
    ];

    protected $casts = [
        'targets' => 'collection',
        'primes' => 'collection',
        'config' => 'array'
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withDefault(
            [
                'name' => 'Unknown',
                'id' => 0
            ]
        );
    }

    public function getCreatedReadableAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getUpdatedReadableAttribute()
    {
        return $this->updated_at->diffForHumans();
    }
}
