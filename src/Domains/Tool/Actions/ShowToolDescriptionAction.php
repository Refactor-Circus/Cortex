<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionShowingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionShownActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;

final class ShowToolDescriptionAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(string $tool): ToolDescriptionModel
    {
        ToolDescriptionShowingActionEvent::dispatch($tool);

        $result = $this->perform($tool);

        ToolDescriptionShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $tool): ToolDescriptionModel
    {
        return ToolDescriptionModel::query()
            ->where('tool', $tool)
            ->with('publishedVersion')
            ->firstOrFail();
    }
}
