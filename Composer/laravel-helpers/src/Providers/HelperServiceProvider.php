<?php

namespace Remp\LaravelHelpers\Providers;

use Blade;
use Remp\LaravelHelpers\MySqlGrammarWithRfcTimezone;
use Request;
use Response;
use Schema;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Carbon::setToStringFormat(DATE_RFC3339);
        Schema::defaultStringLength(191);

        // Do not strip timezone information from Carbon/DateTimeInferface objects when quering MySQL DB
        $connection = $this->app['db.connection'];
        if ($connection instanceof MySqlConnection){
            $connection->setQueryGrammar(new MySqlGrammarWithRfcTimezone());
        }

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
            return "\Psy\Util\Json::encode({$expression})";
        });

        Blade::directive('class', function ($expression) {
            return "<?php echo blade_class({$expression}); ?>";
        });
    }
}
