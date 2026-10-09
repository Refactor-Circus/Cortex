<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Http\Request;

abstract class ToolDescriptionRequest extends Request
{
    /**
     * The registered tool name from the route, verified against the registry.
     */
    protected function tool(): string
    {
        $tool = $this->route('tool');

        if (! is_string($tool) || ! app(ToolRegistry::class)->has($tool)) {
            abort(404);
        }

        return $tool;
    }

    protected function description(): ToolDescriptionModel
    {
        $description = ToolDescriptionModel::query()->where('tool', $this->tool())->first();

        if ($description === null) {
            abort(404);
        }

        return $description;
    }

    /**
     * The version named in the route.
     */
    protected function version(): ToolDescriptionVersionModel
    {
        /** @var ToolDescriptionVersionModel */
        return $this->description()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
    }

    /**
     * The override for the tool, or an unsaved one when no version exists yet, so
     * creating the first version is checked against the same policy.
     */
    protected function descriptionOrNew(): ToolDescriptionModel
    {
        return ToolDescriptionModel::query()->firstOrNew(['tool' => $this->tool()]);
    }
}
