<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use JayI\Cortex\Mcp\Tools\RunVirtualAgentTool;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\DbAgent;

it('runs an agent and returns text with usage', function () {
    DbAgent::fake(['Hello from the agent.']);
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    mcpTool(RunVirtualAgentTool::class, ['slug' => 'helper', 'input' => 'Hi'])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->where('text', 'Hello from the agent.')
                ->has('usage.prompt_tokens')
                ->etc(),
        );

    DbAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});

it('validates run input', function () {
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    mcpTool(RunVirtualAgentTool::class, ['slug' => 'helper'])
        ->assertHasErrors();
});

it('errors not found for unknown agents', function () {
    mcpTool(RunVirtualAgentTool::class, ['slug' => 'missing', 'input' => 'Hi'])
        ->assertHasErrors(['Not found.']);
});
