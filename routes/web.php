<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelUploadController;
use App\Http\Controllers\RowsController;




Route::get('/', [ExcelUploadController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [ExcelUploadController::class, 'handleUpload'])->name('upload.handle');

Route::get('rows', [RowsController::class, 'index']);