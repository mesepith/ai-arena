<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/chat', [ChatController::class, 'index']);
// Route::post('/chat', [ChatController::class, 'store']);

Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
Route::post('/chat/delete/{id}', [ChatController::class, 'delete'])->name('chat.delete');



Route::get('/editor', function () {
    return view('editor');
});
Route::get('/display-markdown', [ChatController::class, 'displayMarkdownAsHtml']);

Route::get('/equations', function () {
    return view('equations');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
