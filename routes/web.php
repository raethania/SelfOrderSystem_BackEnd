<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-seeder', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return 'Seeder has been run successfully!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
