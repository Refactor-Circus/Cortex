<?php

declare(strict_types=1);

namespace JayI\Cortex\Runtime;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;

/**
 * A virtual agent built from its database row, ready to run on laravel/ai.
 * As a sub-agent it is offered to the parent under its slug.
 */
final class DbAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    /**
     * @param  iterable<int, Tool|Agent>  $agentTools
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        private readonly string $agentInstructions,
        private readonly iterable $agentTools = [],
        private readonly ?string $agentProvider = null,
        private readonly ?string $agentModel = null,
        private readonly array $settings = [],
        private readonly string $agentName = 'DbAgent',
        private readonly ?string $agentDescription = null,
    ) {}

    public function name(): string
    {
        return $this->agentName;
    }

    public function description(): string
    {
        return $this->agentDescription ?? sprintf(
            'Delegates a task to the %s sub-agent and returns its response. Pass a clear, self-contained task description as the sub-agent runs in isolation and has no access to the parent conversation history.',
            $this->agentName,
        );
    }

    public function instructions(): string
    {
        return $this->agentInstructions;
    }

    /**
     * @return iterable<int, Tool|Agent>
     */
    public function tools(): iterable
    {
        return $this->agentTools;
    }

    public function provider(): ?string
    {
        return $this->agentProvider;
    }

    public function model(): ?string
    {
        return $this->agentModel;
    }

    public function temperature(): ?float
    {
        $value = $this->settings['temperature'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    public function maxSteps(): ?int
    {
        $value = $this->settings['max_steps'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function maxTokens(): ?int
    {
        $value = $this->settings['max_tokens'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function topP(): ?float
    {
        $value = $this->settings['top_p'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }
}
