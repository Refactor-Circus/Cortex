<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Actions;

use JayI\Cortex\Domains\McpServer\Events\McpInstructionShowingActionEvent;
use JayI\Cortex\Domains\McpServer\Events\McpInstructionShownActionEvent;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;

final class ShowMcpInstructionAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(string $server): McpInstructionModel
    {
        McpInstructionShowingActionEvent::dispatch($server);

        $result = $this->perform($server);

        McpInstructionShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $server): McpInstructionModel
    {
        return McpInstructionModel::query()
            ->where('server', $server)
            ->with('publishedVersion')
            ->firstOrFail();
    }
}
