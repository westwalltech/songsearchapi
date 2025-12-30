<?php

use Illuminate\Support\Facades\Route;
use NewSong\SongSearch\Http\Controllers\SongSearchController;

Route::prefix('song-search')->name('song-search.')->group(function () {
    Route::get('/search', [SongSearchController::class, 'search'])
        ->name('search');

    Route::post('/download-artwork', [SongSearchController::class, 'downloadArtwork'])
        ->name('download-artwork');
});
