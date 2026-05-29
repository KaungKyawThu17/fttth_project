<?php

namespace App\Providers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::viaRequest('api-token', function (Request $request) {
            $plainTextToken = $request->bearerToken();

            if (blank($plainTextToken)) {
                return null;
            }

            $token = ApiToken::query()
                ->with('user')
                ->where('token_hash', hash('sha256', $plainTextToken))
                ->first();

            if (! $token instanceof ApiToken || $token->isExpired()) {
                return null;
            }

            $token->forceFill([
                'last_used_at' => now(),
            ])->save();

            return $token->user;
        });
    }
}
