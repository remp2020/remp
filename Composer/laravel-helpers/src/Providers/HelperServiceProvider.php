<?php

namespace Remp\LaravelHelpers\Providers;

use Blade;
use Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

class HelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Carbon::setToStringFormat(DATE_RFC3339);
        Schema::defaultStringLength(191);
    }

    public function register()
    {
        $this->app->call([$this, 'responseMacros']);
        $this->app->call([$this, 'bladeDirectives']);
    }

    public function responseMacros(Request $request, ResponseFactory $response)
    {
        $response->macro('format', function ($formats) use ($request, $response) {
            if ($request->wantsJson() && array_key_exists('json', $formats)) {
                return $formats['json'];
            }
            return $formats['html'];
        });
    }

    public function bladeDirectives()
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
}
