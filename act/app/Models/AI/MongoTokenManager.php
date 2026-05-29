<?php

namespace App\Models\AI;

use MongoDB\Laravel\Eloquent\Model;

class MongoTokenManager extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'global_token_manager';

    protected $fillable = [
        'user_id',
        'overall_token_used',
        'platforms',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'overall_token_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    // Always ensure platforms is an array
    public function getPlatformsAttribute($value)
    {
        if (is_array($value)) return $value;
        $decoded = is_string($value) ? json_decode($value, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    public function setPlatformsAttribute($value)
    {
        if (is_string($value)) $value = json_decode($value, true);
        $this->attributes['platforms'] = is_array($value) ? $value : [];
    }
}