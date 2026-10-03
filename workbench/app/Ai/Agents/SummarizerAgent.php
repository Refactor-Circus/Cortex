<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Demo plain laravel/ai agent: Cortex lists and runs it, but cannot override it.
 */
final class SummarizerAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Summarize the given text in three short bullet points.';
    }
}
