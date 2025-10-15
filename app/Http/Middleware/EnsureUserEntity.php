<?php

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use FireflyIII\Services\UserEntityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserEntity
{
    protected UserEntityService $userEntityService;

    public function __construct(UserEntityService $userEntityService)
    {
        $this->userEntityService = $userEntityService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Ensure user has a financial entity
            $this->userEntityService->ensureUserEntity($user);
        }

        return $next($request);
    }
}
