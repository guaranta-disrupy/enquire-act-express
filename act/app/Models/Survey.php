<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $appends = [
        'test_count',
    ];

    protected $casts = [
        'config' => 'array'
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class)->orderBy('part', 'asc');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function getTestCountAttribute()
    {
        return $this->tests->count();
    }

    public function getCompletesAttribute()
    {
        return $this->records()->count();
    }

    public function exportables(): HasMany
    {
        return $this->hasMany(Exportable::class, 'survey_id', 'id');
    }

    public function ownsSurvey($user)
    {
        return $user->id === $this->parent_survey->user_id;
    }

    public function getParentSurveyAttribute()
    {
        return MainSurvey::where('uuid', $this->config['survey_id'])->first();
    }

    public function templatePrimes(): HasMany
    {
        return $this->hasMany(TemplatePrime::class);
    }

    public function templateTargets(): HasMany
    {
        return $this->hasMany(TemplateTarget::class);
    }
}
