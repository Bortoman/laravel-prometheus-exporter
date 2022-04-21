<?php
declare(strict_types=1);

namespace Bortoman\PrometheusExporter\Providers;

use Illuminate\Contracts\Http\Kernel as ContractKernel;
use Bortoman\PrometheusExporter\Middleware\PrometheusExporterMiddleware;

class LaravelServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            realpath(__DIR__.'/../config/config.php') => config_path('prometheus_exporter.php')]
        );

        if (config('prometheus_exporter.export_all_requests')) {
            $this->app->make(ContractKernel::class)
                ->pushMiddleware(PrometheusExporterMiddleware::class);
        }

        $this->loadRoutesFrom(realpath(__DIR__.'/../routes/routes.php'));
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            realpath(__DIR__.'/../config/config.php'),
            'prometheus_exporter'
        );

        $this->registerCollectorRegistry(
            $this->getAdapter($this->getConfig('adapter'))
        );
    }
}
