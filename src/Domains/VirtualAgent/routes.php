<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\ProviderController;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentController;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentRunController;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentVersionController;

Route::get('virtual-agents', [VirtualAgentController::class, 'index'])->name('virtual-agents.index');
Route::post('virtual-agents', [VirtualAgentController::class, 'store'])->name('virtual-agents.store');
Route::get('virtual-agents/{agent:slug}', [VirtualAgentController::class, 'show'])->name('virtual-agents.show');
Route::patch('virtual-agents/{agent:slug}', [VirtualAgentController::class, 'update'])->name('virtual-agents.update');
Route::delete('virtual-agents/{agent:slug}', [VirtualAgentController::class, 'destroy'])->name('virtual-agents.destroy');

Route::post('virtual-agents/{agent:slug}/run', [VirtualAgentRunController::class, 'store'])->name('virtual-agents.run');

Route::get('virtual-agents/{agent:slug}/versions', [VirtualAgentVersionController::class, 'index'])->name('virtual-agents.versions.index');
Route::post('virtual-agents/{agent:slug}/versions', [VirtualAgentVersionController::class, 'store'])->name('virtual-agents.versions.store');
Route::get('virtual-agents/{agent:slug}/versions/{version}', [VirtualAgentVersionController::class, 'show'])
    ->whereNumber('version')->name('virtual-agents.versions.show');
Route::post('virtual-agents/{agent:slug}/versions/{version}/publish', [VirtualAgentVersionController::class, 'publish'])
    ->whereNumber('version')->name('virtual-agents.versions.publish');

Route::get('providers', [ProviderController::class, 'index'])->name('providers.index');
