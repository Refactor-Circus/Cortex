<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\PlainServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

function publishServerInstructionOverride(string $server, string $content): void
{
    $instruction = McpInstructionModel::query()->create(['server' => $server]);
    $version = $instruction->versions()->create(['version' => 1, 'content' => $content]);

    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    app(PublicationCache::class)->forget(
        app(PublicationCache::class)->mcpInstructionsKey(),
    );

    app()->forgetScopedInstances();
}

it('serves the code-declared instructions without a published override', function () {
    $context = (new CortexServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toContain('Manage Cortex virtual agents');
});

it('serves the published override in the server context', function () {
    publishServerInstructionOverride('cortex', 'Override instructions for the Cortex server.');

    $context = (new CortexServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toBe('Override instructions for the Cortex server.');
});

it('keeps serving code-declared instructions while a version is only drafted', function () {
    $instruction = McpInstructionModel::query()->create(['server' => 'cortex']);
    $instruction->versions()->create(['version' => 1, 'content' => 'Draft instructions.']);

    $context = (new CortexServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toContain('Manage Cortex virtual agents');
});

it('applies overrides to servers registered at runtime', function () {
    app(McpServerRegistry::class)->register('echo', EchoServer::class);

    publishServerInstructionOverride('echo', 'Override instructions for the echo server.');

    $context = (new EchoServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toBe('Override instructions for the echo server.');
});

it('falls back to code-declared instructions for unregistered servers', function () {
    publishServerInstructionOverride('plain-server', 'Never served.');

    $context = (new PlainServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toBe('Property-declared plain server instructions.');
});
