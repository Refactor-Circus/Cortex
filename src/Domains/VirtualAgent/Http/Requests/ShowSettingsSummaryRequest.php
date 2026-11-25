<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowSettingsSummaryAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\SettingsSummaryResource;
use RefactorCircus\Cortex\Http\Request;

/**
 * Like the providers, tools and servers lists, the summary asks no policy:
 * there is no model to ask one of, and the route middleware is its gate.
 */
final class ShowSettingsSummaryRequest extends Request
{
    public function persist(): JsonResponse
    {
        return (new SettingsSummaryResource(app(ShowSettingsSummaryAction::class)->execute()))->response();
    }
}
