<?php

use Illuminate\Support\Facades\Route;

Route::get('metrics', 'Bortoman\PrometheusExporter\Controllers\LaravelMetricsController@metrics')
    ->name('metrics');
