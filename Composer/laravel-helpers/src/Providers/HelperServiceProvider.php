<?php

namespace Remp\LaravelHelpers\Providers;

use Carbon\FactoryImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        FactoryImmutable::getDefaultInstance()->setToStringFormat(DATE_RFC3339);
        Schema::defaultStringLength(191);

        $this->responseMacros();
        $this->bladeDirectives();
    }

    public function responseMacros()
    {
        Response::macro('format', function ($formats) {
            if (Request::wantsJson() && array_key_exists('json', $formats)) {
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
            return "\json_encode({$expression}, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)";
        });

        Blade::directive('class', function ($expression) {
            return "<?php echo blade_class({$expression}); ?>";
        });
    }
}
