<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question_Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';
    protected $guarded = [];
    protected $table = 'question_templates';
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


    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuestionTemplate_Language::class, 'question_language_id', 'id');
    }

}
