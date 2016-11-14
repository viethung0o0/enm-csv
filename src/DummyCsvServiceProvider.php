<?php

namespace Enigmacsv\DummyCsv;

use Illuminate\Support\ServiceProvider;

class DummyCsvServiceProvider extends ServiceProvider
{

    /**
     * Get the active router.
     *
     * @return Application
     */
    protected function getRouter()
    {
        return $this->app;
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function root()
    {
        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->getConfigPath(), 'dummy_csv'
        );

        $routeConfig = [
            'namespace' => 'Enigmacsv\DummyCsv\Controllers',
            'prefix' => 'dummy/csv',
        ];
        $this->getRouter()->group($routeConfig, function($router) {
            $router->get('employee', 'DummyCsvController@exportCsvEmployee');
            $router->get('kintai', 'DummyCsvController@exportCsvKintai');
        });
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return __DIR__ . '/../config/dummycsv.php';
    }
}
