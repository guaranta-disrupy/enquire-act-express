<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MainSurvey extends Model
{
    use HasFactory;

    protected $connection = 'mysql_auth';

    protected $guarded = [];

    protected $table = 'surveys';

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function ownsSurvey($user): bool
    {
        return $user->id === $this->user_id;
    }

    public function wasCompleted() : Attribute
    {
        return Attribute::get(function() {
            return $this->status === 'complete';
        });
    }

    public function wasPaused() : Attribute
    {
        return Attribute::get(function() {
            return $this->status === 'pause';
        });
    }

    public function isNotLive() : Attribute
    {
        return Attribute::get(function() {
            return $this->status !== 'live';
        });
    }
}
