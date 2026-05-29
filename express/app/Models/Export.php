<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Export extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $guarded = [];
    protected $dates = ['deleted_at'];

    protected $casts = [
        'data' => 'json',
        'setup' => 'json',
    ];

    public function questionnaire(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'project_id', 'id');
    }
}
