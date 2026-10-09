<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\Domains\Tool\Http\Controllers\ToolController;
use RefactorCircus\Cortex\Domains\Tool\Http\Controllers\ToolDescriptionController;

Route::get('tools', [ToolController::class, 'index'])->name('tools.index');

Route::get('tools/{tool}/description', [ToolDescriptionController::class, 'show'])->name('tools.description.show');
Route::delete('tools/{tool}/description', [ToolDescriptionController::class, 'destroy'])->name('tools.description.destroy');
Route::get('tools/{tool}/description/versions', [ToolDescriptionController::class, 'versions'])->name('tools.description.versions.index');
Route::post('tools/{tool}/description/versions', [ToolDescriptionController::class, 'store'])->name('tools.description.versions.store');
Route::post('tools/{tool}/description/versions/{version}/publish', [ToolDescriptionController::class, 'publish'])
    ->whereNumber('version')->name('tools.description.versions.publish');
