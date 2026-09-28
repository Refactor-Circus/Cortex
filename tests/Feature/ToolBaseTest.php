<?php

declare(strict_types=1);

use JayI\Cortex\Mcp\Tools\CreateVirtualAgentTool;
use JayI\Cortex\Models\ToolDescription;
use JayI\Cortex\Support\PublicationCache;
use JayI\Cortex\Tests\Fixtures\EchoCortexTool;
use JayI\Cortex\Tools\ToolRegistry;

function publishToolDescriptionOverride(string $tool, string $content): void
{
    $description = ToolDescription::query()->create(['tool' => $tool]);
    $version = $description->versions()->create(['version' => 1, 'content' => $content]);

    $description->published_version_id = $version->getKey();
    $description->save();

    app(PublicationCache::class)->forget(
        app(PublicationCache::class)->toolDescriptionsKey(),
    );
}

it('serves the code-declared description without a published override', function () {
    expect(app(EchoCortexTool::class)->description())
        ->toBe('Echoes back the given message from the Cortex base tool.');
});

it('serves the published override in the MCP tool definition', function () {
    publishToolDescriptionOverride('echo-cortex-tool', 'Override for MCP clients.');

    expect(app(EchoCortexTool::class)->toArray()['description'])
        ->toBe('Override for MCP clients.');
});

it('serves the published override to agents through the registry', function () {
    publishToolDescriptionOverride('echo-cortex-tool', 'Override for agents.');

    $registry = app(ToolRegistry::class);
    $registry->register('echo-cortex-tool', EchoCortexTool::class);

    expect((string) $registry->get('echo-cortex-tool')->description())
        ->toBe('Override for agents.');
});

it('applies overrides to the package management tools', function () {
    publishToolDescriptionOverride('create-virtual-agent-tool', 'Create agents, but described differently.');

    expect(app(CreateVirtualAgentTool::class)->toArray()['description'])
        ->toBe('Create agents, but described differently.');
});
