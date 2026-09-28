<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Cortex\Http\Controllers\ConcreteAgentController;
use JayI\Cortex\Http\Controllers\ConcreteAgentVersionController;
use JayI\Cortex\Http\Controllers\McpInstructionController;
use JayI\Cortex\Http\Controllers\McpServerController;
use JayI\Cortex\Http\Controllers\ProviderController;
use JayI\Cortex\Http\Controllers\ToolController;
use JayI\Cortex\Http\Controllers\ToolDescriptionController;
use JayI\Cortex\Http\Controllers\VirtualAgentController;
use JayI\Cortex\Http\Controllers\VirtualAgentRunController;
use JayI\Cortex\Http\Controllers\VirtualAgentVersionController;

/** @var string $prefix */
$prefix = config('cortex.routes.prefix');

/** @var array<int, string> $middleware */
$middleware = config('cortex.routes.middleware');

Route::prefix($prefix)->middleware($middleware)->name('cortex.')->group(function (): void {
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

    Route::get('concrete-agents', [ConcreteAgentController::class, 'index'])->name('concrete-agents.index');
    Route::get('concrete-agents/{agent}', [ConcreteAgentController::class, 'show'])->name('concrete-agents.show');
    Route::post('concrete-agents/{agent}/run', [ConcreteAgentController::class, 'run'])->name('concrete-agents.run');
    Route::put('concrete-agents/{agent}/tools', [ConcreteAgentController::class, 'tools'])->name('concrete-agents.tools.update');
    Route::delete('concrete-agents/{agent}/override', [ConcreteAgentController::class, 'destroy'])->name('concrete-agents.override.destroy');

    Route::get('concrete-agents/{agent}/versions', [ConcreteAgentVersionController::class, 'index'])->name('concrete-agents.versions.index');
    Route::post('concrete-agents/{agent}/versions', [ConcreteAgentVersionController::class, 'store'])->name('concrete-agents.versions.store');
    Route::post('concrete-agents/{agent}/versions/{version}/publish', [ConcreteAgentVersionController::class, 'publish'])
        ->whereNumber('version')->name('concrete-agents.versions.publish');

    Route::get('providers', [ProviderController::class, 'index'])->name('providers.index');

    Route::get('tools', [ToolController::class, 'index'])->name('tools.index');

    Route::get('tools/{tool}/description', [ToolDescriptionController::class, 'show'])->name('tools.description.show');
    Route::delete('tools/{tool}/description', [ToolDescriptionController::class, 'destroy'])->name('tools.description.destroy');
    Route::get('tools/{tool}/description/versions', [ToolDescriptionController::class, 'versions'])->name('tools.description.versions.index');
    Route::post('tools/{tool}/description/versions', [ToolDescriptionController::class, 'store'])->name('tools.description.versions.store');
    Route::post('tools/{tool}/description/versions/{version}/publish', [ToolDescriptionController::class, 'publish'])
        ->whereNumber('version')->name('tools.description.versions.publish');

    Route::get('servers', [McpServerController::class, 'index'])->name('servers.index');

    Route::get('servers/{server}/instructions', [McpInstructionController::class, 'show'])->name('servers.instructions.show');
    Route::delete('servers/{server}/instructions', [McpInstructionController::class, 'destroy'])->name('servers.instructions.destroy');
    Route::get('servers/{server}/instructions/versions', [McpInstructionController::class, 'versions'])->name('servers.instructions.versions.index');
    Route::post('servers/{server}/instructions/versions', [McpInstructionController::class, 'store'])->name('servers.instructions.versions.store');
    Route::post('servers/{server}/instructions/versions/{version}/publish', [McpInstructionController::class, 'publish'])
        ->whereNumber('version')->name('servers.instructions.versions.publish');
});
