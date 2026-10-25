<?php

namespace App\Providers;

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Auth\CustomAuthController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, CustomAuthController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (Auth::attempt($credentials)) {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                if ($user) {
                    app(\App\Services\UserPasswordService::class)
                        ->ensurePlainFilled($user, (string) ($credentials['password'] ?? ''));
                }

                return $user;
            }

            return null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());
            $perMinute = (int) env('FORTIFY_LOGIN_PER_MINUTE', 300);
            if ($perMinute <= 0) {
                return Limit::none();
            }

            return Limit::perMinute(max(1, $perMinute))->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            $perMinute = (int) env('FORTIFY_TWO_FACTOR_PER_MINUTE', 60);
            if ($perMinute <= 0) {
                return Limit::none();
            }
            $by = $request->session()->get('login.id') ?: $request->ip();

            return Limit::perMinute(max(1, $perMinute))->by($by);
        });
    }
}
