<?php

namespace App\Policies;

use App\Models\MainSurvey;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\Roles;
use App\Models\SurveyUser;

class MainSurveyPolicy
{

    use HandlesAuthorization;
    use Roles;

    public function before(User $user)
    {
        if ($user->role === 'root') {
            return true;
        }
    }

    private function getPermissions($user, $survey)
    {
        $survey_membership = SurveyUser::where('survey_id', $survey->id)->where('user_id', $user->id)->first();
        // info($survey);
        if (is_null($survey_membership))
            return [];
        
        return collect($this->available_roles)->filter(fn($v) => $v['key'] === $survey_membership->role)->first()['permissions'];
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MainSurvey $mainSurvey): bool
    {
        if ($mainSurvey->ownsSurvey($user)) {
            return true;
        }
        $is_authorize = $user->belongsToTeam($mainSurvey->team) && $user->is_admin;
        if ($is_authorize) {
            return true;
        } else {
            return in_array('read', $this->getPermissions($user, $mainSurvey));
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MainSurvey $mainSurvey): bool
    {
        $is_authorize = $user->ownsTeam($mainSurvey->team) || $user->id === $mainSurvey->user_id;
        if ($is_authorize) {
            return true;
        } else {
            return in_array('update', $this->getPermissions($user, $mainSurvey));
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MainSurvey $mainSurvey): bool
    {
        if (($user->hasTeamRole($mainSurvey->team, 'admin') && $user->belongsToTeam($mainSurvey->team)) || $mainSurvey->user_id === $user->id) {
            return true;
        } else {
            return in_array('delete', $this->getPermissions($user, $mainSurvey));
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MainSurvey $mainSurvey): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MainSurvey $mainSurvey): bool
    {
        return false;
    }
}
