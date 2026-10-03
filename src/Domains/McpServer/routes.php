<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Cortex\Domains\McpServer\Http\Controllers\McpInstructionController;
use JayI\Cortex\Domains\McpServer\Http\Controllers\McpServerController;

Route::get('servers', [McpServerController::class, 'index'])->name('servers.index');

Route::get('servers/{server}/instructions', [McpInstructionController::class, 'show'])->name('servers.instructions.show');
Route::delete('servers/{server}/instructions', [McpInstructionController::class, 'destroy'])->name('servers.instructions.destroy');
Route::get('servers/{server}/instructions/versions', [McpInstructionController::class, 'versions'])->name('servers.instructions.versions.index');
Route::post('servers/{server}/instructions/versions', [McpInstructionController::class, 'store'])->name('servers.instructions.versions.store');
Route::post('servers/{server}/instructions/versions/{version}/publish', [McpInstructionController::class, 'publish'])
    ->whereNumber('version')->name('servers.instructions.versions.publish');
