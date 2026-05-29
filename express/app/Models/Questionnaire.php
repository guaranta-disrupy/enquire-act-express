<?php

namespace App\Models;

use App\Events\QuestionnaireUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Questionnaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'setup' => 'json',
        'deleted_at' => 'datetime'
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question::class, 'questionnaire_id', 'id')->orderBy('order');
    }

    public function galleries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Gallery::class, 'questionnaire_id', 'id')->orderBy('order');
    }

    public function exports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Export::class, 'questionnaire_id', 'id')->orderBy('order');
    }

    public function getParentSurveyAttribute()
    {
        return MainSurvey::where('uuid', $this->setup['survey_id'])->first();
    }

    /**
     * Boot the model and register event listeners to monitor questionnaire-level changes.
     */
    protected static function booted()
    {
        static::created(function (Questionnaire $questionnaire) {
            Log::info('[QuestionnaireModel] created hook triggered', [
                'questionnaire_id' => $questionnaire->id,
            ]);

            $questionnaire->broadcastQuestionnaireChange('questionnaire_created');
        });

        static::updated(function (Questionnaire $questionnaire) {
            Log::info('[QuestionnaireModel] updated hook triggered', [
                'questionnaire_id' => $questionnaire->id,
            ]);

            $questionnaire->broadcastQuestionnaireChange('questionnaire_updated');
        });

        static::deleted(function (Questionnaire $questionnaire) {
            Log::info('[QuestionnaireModel] deleted hook triggered', [
                'questionnaire_id' => $questionnaire->id,
            ]);

            $questionnaire->broadcastQuestionnaireChange('questionnaire_deleted');
        });

        static::restored(function (Questionnaire $questionnaire) {
            Log::info('[QuestionnaireModel] restored hook triggered', [
                'questionnaire_id' => $questionnaire->id,
            ]);

            $questionnaire->broadcastQuestionnaireChange('questionnaire_restored');
        });
    }

    /**
     * Broadcast a questionnaire-updated event over websockets when the questionnaire itself changes.
     */
    protected function broadcastQuestionnaireChange(string $updateType): void
    {
        Log::info('[QuestionnaireModel] Broadcasting questionnaire update', [
            'update_type' => $updateType,
            'questionnaire_id' => $this->id,
            'user_id' => Auth::id(),
        ]);

        event(new QuestionnaireUpdated(
            questionnaireId: $this->id,
            updateType: $updateType,
            data: [
                'questionnaire_code' => $this->code ?? null,
                'questionnaire_name' => $this->name ?? null,
            ],
            user: Auth::user()
        ));
    }
}
