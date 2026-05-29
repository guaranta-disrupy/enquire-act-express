<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyUser extends Model
{
    use HasFactory;

    protected $connection = 'mysql_auth';

    protected $table = 'survey_user';

    protected $guarded = [];

    public function surveys(): HasOne
    {
        return $this->hasOne(MainSurvey::class, 'survey_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
