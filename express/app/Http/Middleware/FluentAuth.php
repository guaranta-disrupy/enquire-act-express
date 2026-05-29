<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RedirectToken;

class FluentAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!Auth::check()) {
            $this->handleToken($request);
        } else {
            if (Auth::user()->is_logged_out) {
                $this->logout($request);
            }
            $this->handleToken($request, true);
        }
        return $next($request);
    }

    private function handleToken(Request $request, $authenticated = false)
    {
        if ($authenticated) {
            if ($request->has('uid')) {
                $token = RedirectToken::where('token', $request->input('uid'))
                    ->first();
                if ($token) {
                    if (!$token->used) {
                        $token->used = true;
                        $token->save();
                    } else {
                        $token->delete();
                    }
                }
            }
            return;
        }
        if (!$request->has('uid')) {
            return $this->redirectToLogin();
        }

        $token = RedirectToken::where('token', $request->input('uid'))->first();

        abort_if(!$token, 404, "Access token not found.");
        abort_if($token->used, 403, "This action is Unauthorized.");

        $user = $token->user;

        if (is_null($token->user) || !is_null($token->user->logout_at)) {
            return $this->redirectToLogin();
        }

        $token->used = true;
        $token->save();
        Auth::guard('web')->loginUsingId($user->id);
        return true;
    }

    private function redirectToLogin()
    {
        return redirect(env('APP_FLUENT_URL') . '/login');
    }

    private function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $this->redirectToLogin();
    }
}
