<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowSettingsSummaryAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\SettingsSummaryResource;
use RefactorCircus\Cortex\Mcp\Request;

/**
 * Like the providers, tools and servers lists, the summary asks no policy:
 * there is no model to ask one of.
 */
final class ShowSettingsSummaryMcpRequest extends Request
{
    protected function handle(array $validated): ResponseFactory
    {
        return (new SettingsSummaryResource(app(ShowSettingsSummaryAction::class)->execute()))->toStructuredResponse();
    }
}
