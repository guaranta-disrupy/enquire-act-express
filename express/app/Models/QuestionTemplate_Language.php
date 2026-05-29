<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionTemplate_Language extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'question_template_languages';

    public function templates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question_Template::class, 'question_language_id', 'id');
    }
}
