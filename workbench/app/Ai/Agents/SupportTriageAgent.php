<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Agents;

use JayI\Cortex\Domains\ConcreteAgent\Support\Agent;
use Workbench\App\Domains\Support\Tools\CreateTicketTool;
use Workbench\App\Domains\Support\Tools\SearchKnowledgeBaseTool;

/**
 * Demo concrete agent whose prompt and toolset Cortex overrides.
 */
final class SupportTriageAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'You triage incoming support requests. Answer from the knowledge base when you can, otherwise open a ticket.';
    }

    public function defaultTools(): iterable
    {
        return [new SearchKnowledgeBaseTool, new CreateTicketTool];
    }
}
