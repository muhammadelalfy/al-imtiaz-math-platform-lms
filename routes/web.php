<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/{path?}', fn () => Inertia::render('Lms'))
    ->where('path', '.*');
