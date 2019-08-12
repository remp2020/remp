<?php

namespace App\Providers;

use App\Account;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    const SELECTED_PROPERTY_TOKEN_UUID = 'selected_property_token_uuid';

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        Paginator::useBootstrapThree();

        // Global selector of current property token
        View::composer('*', function ($view) {
            $selectedPropertyTokenUuid = \Session::get('SELECTED_PROPERTY_TOKEN_UUID');
            $tokens = [
                (object)[
                    'uuid' => null,
                    'name' => 'All tokens',
                    'selected' => true,
                ]
            ];

            foreach (Account::all() as $account) {
                $selected = $account->uuid === $selectedPropertyTokenUuid;
                if ($selected) {
                    $tokens[0]->selected = false;
                }

                $tokens[] = (object)[
                    'uuid' => $account->uuid,
                    'name' => $account->name,
                    'selected' => $selected
                ];
            }

            $view->with('propertyTokens', $tokens);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
