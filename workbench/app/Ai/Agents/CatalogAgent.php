<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Agents;

use JayI\Cortex\Agents\Agent;
use Workbench\App\Domains\Catalog\Tools\CheckInventoryTool;

/**
 * Demo concrete agent that keeps the prompt declared here.
 */
final class CatalogAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'You answer product availability questions. Always check inventory before promising a delivery date.';
    }

    public function defaultTools(): iterable
    {
        return [new CheckInventoryTool];
    }
}
