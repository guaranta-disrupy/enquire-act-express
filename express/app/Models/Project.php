<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MainSurvey;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];
    protected $casts = ['deleted_at' => 'datetime'];

    protected $hidden = [
        'id'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function questionnaires(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Questionnaire::class, 'project_id', 'id');
    }

    public function galleries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Gallery::class, 'project_id', 'id');
    }

    public function filters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Filter::class, 'project_id', 'id');
    }

    public function parent_survey(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MainSurvey::class, 'code', 'code');
    }
}
