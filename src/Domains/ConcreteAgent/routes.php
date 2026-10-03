<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentController;
use JayI\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentVersionController;

Route::get('concrete-agents', [ConcreteAgentController::class, 'index'])->name('concrete-agents.index');
Route::get('concrete-agents/{agent}', [ConcreteAgentController::class, 'show'])->name('concrete-agents.show');
Route::post('concrete-agents/{agent}/run', [ConcreteAgentController::class, 'run'])->name('concrete-agents.run');
Route::put('concrete-agents/{agent}/tools', [ConcreteAgentController::class, 'tools'])->name('concrete-agents.tools.update');
Route::delete('concrete-agents/{agent}/override', [ConcreteAgentController::class, 'destroy'])->name('concrete-agents.override.destroy');

Route::get('concrete-agents/{agent}/versions', [ConcreteAgentVersionController::class, 'index'])->name('concrete-agents.versions.index');
Route::post('concrete-agents/{agent}/versions', [ConcreteAgentVersionController::class, 'store'])->name('concrete-agents.versions.store');
Route::post('concrete-agents/{agent}/versions/{version}/publish', [ConcreteAgentVersionController::class, 'publish'])
    ->whereNumber('version')->name('concrete-agents.versions.publish');
