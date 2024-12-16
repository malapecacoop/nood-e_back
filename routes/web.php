<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (config('app.debug')) {
        return ['Laravel' => app()->version()];
    }

    return '';
});
