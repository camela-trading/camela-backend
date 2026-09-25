<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $email = $request->query('email');

    return redirect()->away($frontendUrl . '/forgot-password?token=' . urlencode($token) . '&email=' . urlencode($email));
})->name('password.reset');

Route::get('/storage/{path}', function (string $path) {
    $decodedPath = ltrim(rawurldecode($path), '/');

    if (! Storage::disk('public')->exists($decodedPath)) {
        abort(404);
    }

    return Storage::disk('public')->response($decodedPath);
})->where('path', '.*');
