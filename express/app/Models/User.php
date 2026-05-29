<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;

    protected $connection = 'mysql_auth';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (app()->environment('production')) {
            $this->useVerifyEmailTrait();
        }
    }

    protected function useVerifyEmailTrait()
    {
        $this->addMustVerifyEmail();
    }

    protected function addMustVerifyEmail()
    {
        class_implements($this)[MustVerifyEmail::class] = MustVerifyEmail::class;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'role', 
        'expired_at', 
        'current_team_id', 
        'is_active', 
        '__tokens', 
        'uuid', 
        'logout_at', 
        'google_id',
        'email_verified_at'
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'expired_at' => 'datetime',
        'logout_at' => 'datetime'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'id');
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'user_id', 'id');
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class, 'user_id', 'id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Activity_Log::class, 'user_id', 'id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(MainSurvey::class, 'user_id', 'id');
    }

    public function questions(){
        return $this->hasMany(Question::class, 'user_id', 'id');
    }

    /**
     * Determine if the use is a root admin.
     * 
     * @return Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function super(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['role'] === 'root'
        );
    }

    /**
     * Determine if the user is an admin.
     * 
     * @return Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['role'] === 'admin',
        );
    }

    protected  function isLoggedOut(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => !empty($attributes['logout_at'])
        );
    }

    public function shouldLogout()
    {
        $this->update([
            'logout_at' => now()
        ]);
    }

    /**
     * Get all of the teams the user owns or belongs to.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allTeams()
    {
        if ($this->super) {
            return Team::where('personal_team', false)->get()->merge(collect([$this->personalTeam()]))->sortBy('name');
        }
        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        if (! $this->belongsToTeam($team) && $this->super == false) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }
}
