<?php

namespace App\Providers;

use App\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;
use Blade;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->bindBladeDirectives();
    }

    public function bindBladeDirectives()
    {
        Blade::directive('yesno', function ($expression) {
            return "{$expression} ? 'Yes' : 'No'";
        });

        Blade::directive('json', function ($expression) {
            return "\Psy\Util\Json::encode({$expression})";
        });

        Blade::directive('class', function ($expression) {
            return "<?php echo blade_class({$expression}); ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        $this->app->call([$this, 'responseMacros']);
    }

    public function responseMacros(Request $request, ResponseFactory $response)
    {
        $response->macro('format', function ($formats) use ($request, $response)
        {
            if ($request->wantsJson() && array_key_exists('json', $formats)) {
                return $formats['json'];
            }
            return $formats['html'];
        });
    }
}
