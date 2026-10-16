<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'application' => 'Plateforme scolaire',
        'message' => 'L’API est disponible sous /api. L’interface Vue.js se lance séparément.',
    ]);
});
