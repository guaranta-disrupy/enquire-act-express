<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'project_id', 'id');
    }

    public function filters(): HasMany
    {
        return $this->hasMany(Filter::class, 'project_id', 'id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(Variable::class, 'project_id', 'id');
    }

    public function surveys(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Survey::class, Group::class);
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
