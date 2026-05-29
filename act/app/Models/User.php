<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasUuid;

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
     * @var array<int, string>
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
        'email_verified_at',
        'is_exempt_from_subscription'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
     * @var array<string, string>
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
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'notification_count'
        // 'encrypted_email'
    ];

    public function surveys(): HasMany
    {
        return $this->hasMany(MainSurvey::class, 'user_id', 'id');
    }


    public function panels(): HasMany
    {
        return $this->hasMany(Survey::class, 'user_id', 'id');
    }

    public function templatePrimes(): HasMany
    {
        return $this->hasMany(TemplatePrime::class);
    }

    public function templateTargets(): HasMany
    {
        return $this->hasMany(TemplateTarget::class);
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
            get: fn($value, $attributes) => !is_null($attributes['logout_at'])
        );
    }

    public function getEncryptedEmailAttribute()
    {
        return http_build_query(['email' => Crypt::encrypt($this->email)]);
    }


    public function getIsSubscriptionValidAttribute()
    {
        return Carbon::now()->diffInSeconds($this->expired_at, false) > 0;
    }

    public function getNotificationCountAttribute()
    {
        return auth()->check() ? $this->unreadNotifications()->count() : 0;
    }

    protected function defaultProfilePhotoUrl()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&bold=true';
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


    public function hasCookieConsent()
    {
        return $this->cookie_consent !== null;
    }

}
