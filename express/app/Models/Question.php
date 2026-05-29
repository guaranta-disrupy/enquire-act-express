<?php

namespace App\Models;

use App\Events\QuestionnaireUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Question extends Model
{
    use HasFactory,SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];
    protected $casts = [
        'setup' => 'json',
        'choices' => 'json',
        'rows' => 'json',
        'columns' => 'json',
        'items' => 'json',
        'item1' => 'json',
        'item2' => 'json',
        'values' => 'json',
        'deleted_at' => 'datetime'
    ];

    public function questionnaire(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id', 'id');
    }

    public function updateInfo(){
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Boot the model and register event listeners to monitor changes.
     */
    protected static function booted()
    {
        static::created(function (Question $question) {
            Log::info('[QuestionModel] created hook triggered', [
                'question_id' => $question->id,
                'questionnaire_id' => $question->questionnaire_id,
            ]);
            $question->broadcastQuestionChange('question_created');
        });

        static::updated(function (Question $question) {
            Log::info('[QuestionModel] updated hook triggered', [
                'question_id' => $question->id,
                'questionnaire_id' => $question->questionnaire_id,
            ]);
            $question->broadcastQuestionChange('question_updated');
        });

        static::deleted(function (Question $question) {
            Log::info('[QuestionModel] deleted hook triggered', [
                'question_id' => $question->id,
                'questionnaire_id' => $question->questionnaire_id,
            ]);
            $question->broadcastQuestionChange('question_deleted');
        });

        static::restored(function (Question $question) {
            Log::info('[QuestionModel] restored hook triggered', [
                'question_id' => $question->id,
                'questionnaire_id' => $question->questionnaire_id,
            ]);
            $question->broadcastQuestionChange('question_restored');
        });
    }

    /**
     * Broadcast a questionnaire-updated event over websockets when this question changes.
     */
    protected function broadcastQuestionChange(string $updateType): void
    {
        $questionnaire = $this->questionnaire;

        if (! $questionnaire) {
            return;
        }

        Log::info('[QuestionModel] Broadcasting questionnaire update', [
            'update_type' => $updateType,
            'question_id' => $this->id,
            'questionnaire_id' => $questionnaire->id,
            'user_id' => Auth::id(),
        ]);

        event(new QuestionnaireUpdated(
            questionnaireId: $questionnaire->id,
            updateType: $updateType,
            data: [
                'question_id' => $this->id,
                'questionnaire_code' => $questionnaire->code ?? null,
                'question_order' => $this->order ?? null,
            ],
            user: Auth::user()
        ));
    }
}
