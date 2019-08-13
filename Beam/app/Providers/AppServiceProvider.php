<?php

namespace App\Providers;

use App\Account;
use App\Http\Controllers\PropertyController;
use App\Property;
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
            $selectedPropertyTokenUuid = \Session::get(self::SELECTED_PROPERTY_TOKEN_UUID);
            $accountPropertyTokens = [
                (object) [
                    'name' => null,
                    'tokens' => [
                        (object)[
                        'uuid' => null,
                        'name' => 'All tokens',
                        'selected' => true,
                        ]
                    ]
                ]
            ];

            foreach (Account::all() as $account) {
                $tokens = [];
                foreach ($account->properties as $property) {
                    $selected = $property->uuid === $selectedPropertyTokenUuid;
                    if ($selected) {
                        $accountPropertyTokens[0]->tokens[0]->selected = false;
                    }
                    $tokens[] = (object)[
                        'uuid' => $property->uuid,
                        'name' => $property->name,
                        'selected' => $selected
                    ];
                }

                if (count($tokens) > 0) {
                    $accountPropertyTokens[] = (object) [
                        'name' => $account->name,
                        'tokens' => $tokens
                    ];
                }
            }

            $view->with('accountPropertyTokens', $accountPropertyTokens);
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
