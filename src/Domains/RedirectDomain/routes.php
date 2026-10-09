<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\Domains\RedirectDomain\Http\Controllers\RedirectDomainController;

Route::get('redirect-domains', [RedirectDomainController::class, 'index'])->name('redirect-domains.index');
Route::post('redirect-domains', [RedirectDomainController::class, 'store'])->name('redirect-domains.store');
Route::delete('redirect-domains/{domain}', [RedirectDomainController::class, 'destroy'])->name('redirect-domains.destroy');
