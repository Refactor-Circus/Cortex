<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\ShowSettingsSummaryRequest;

final class SettingsSummaryController
{
    public function show(ShowSettingsSummaryRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
