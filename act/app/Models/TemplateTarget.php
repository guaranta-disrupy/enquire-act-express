<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $fillable = [
        'team_id',
        'project_id',
        'survey_id',
        'user_id',
        'name',
        'data',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forTeam($teamId)
    {
        return static::where('team_id', $teamId)->get();
    }

    public static function forTeamFiltered($teamId, $projectId = null, $surveyId = null)
    {
        $query = static::where('team_id', $teamId);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($surveyId) {
            $query->where('survey_id', $surveyId);
        }
        return $query->get();
    }
}