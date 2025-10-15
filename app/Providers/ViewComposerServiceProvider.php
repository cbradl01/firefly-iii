<?php

namespace FireflyIII\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use FireflyIII\Models\FinancialEntity;
use Illuminate\Support\Facades\Auth;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share financial entity data with layout for breadcrumbs
        View::composer('layout.default', function ($view) {
            $routeName = Route::currentRouteName();
            
            if ($routeName === 'financial-entities.show' || $routeName === 'financial-entities.edit') {
                $entityId = Route::current()->parameter('id') ?? Route::current()->parameter('financialEntity');
                
                if ($entityId) {
                    $user = Auth::user();
                    $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->find($entityId);
                    
                    if ($financialEntity) {
                        $view->with('financialEntity', $financialEntity);
                    }
                }
            }
        });
    }
}
