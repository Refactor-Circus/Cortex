<?php

declare(strict_types=1);

use JayI\Cortex\Cortex;
use JayI\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use JayI\Cortex\Domains\McpServer\Exceptions\McpServerNotFoundException;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Cortex\Mcp\CortexServer;
use JayI\Cortex\Support\PublicationCache;
use JayI\Cortex\Tests\Fixtures\EchoServer;
use JayI\Cortex\Tests\Fixtures\PlainServer;

it('registers and resolves servers at runtime', function () {
    $registry = app(McpServerRegistry::class);

    $registry->register('echo', EchoServer::class);

    expect($registry->has('echo'))->toBeTrue()
        ->and($registry->get('echo'))->toBe(EchoServer::class)
        ->and($registry->names())->toBe(['cortex', 'echo']);
});

it('always registers the cortex server', function () {
    $registry = app(McpServerRegistry::class);

    expect($registry->has('cortex'))->toBeTrue()
        ->and($registry->get('cortex'))->toBe(CortexServer::class);
});

it('loads servers from the package config', function () {
    config()->set('cortex.mcp.servers', ['support' => EchoServer::class]);

    expect(app(McpServerRegistry::class)->has('support'))->toBeTrue();
});

it('rejects classes that are not MCP servers', function () {
    app(McpServerRegistry::class)->register('bad', stdClass::class);
})->throws(InvalidArgumentException::class, 'must extend');

it('throws for unknown server names', function () {
    app(McpServerRegistry::class)->get('missing');
})->throws(McpServerNotFoundException::class, 'MCP server [missing] is not registered.');

it('resolves registered names for server classes', function () {
    $registry = app(McpServerRegistry::class);
    $registry->register('echo', EchoServer::class);

    expect($registry->nameFor(EchoServer::class))->toBe('echo')
        ->and($registry->nameFor(CortexServer::class))->toBe('cortex')
        ->and($registry->nameFor(PlainServer::class))->toBeNull();
});

it('derives names for unkeyed config entries from the server itself', function () {
    config()->set('cortex.mcp.servers', [EchoServer::class, PlainServer::class]);

    expect(app(McpServerRegistry::class)->names())->toBe(['cortex', 'echo-server', 'plain-server']);
});

it('reads default instructions from the attribute or the property', function () {
    $registry = app(McpServerRegistry::class);
    $registry->register('echo', EchoServer::class);
    $registry->register('plain', PlainServer::class);

    expect($registry->defaultInstructions('echo'))->toBe('Code-declared echo server instructions.')
        ->and($registry->defaultInstructions('plain'))->toBe('Property-declared plain server instructions.');
});

it('lists servers with effective instructions', function () {
    $registry = app(McpServerRegistry::class);
    $registry->register('echo', EchoServer::class);

    $instruction = McpInstructionModel::query()->create(['server' => 'echo']);
    $version = $instruction->versions()->create(['version' => 1, 'content' => 'Published echo instructions.']);
    $instruction->published_version_id = $version->getKey();
    $instruction->save();
    app(PublicationCache::class)->forget(app(PublicationCache::class)->mcpInstructionsKey());

    $servers = app(ListMcpServersAction::class)->execute();

    expect($servers)->toHaveCount(2)
        ->and($servers[0]['name'])->toBe('cortex')
        ->and($servers[0]['class'])->toBe(CortexServer::class)
        ->and($servers[0]['instructions'])->toContain('Manage Cortex virtual agents')
        ->and($servers[1]['name'])->toBe('echo')
        ->and($servers[1]['instructions'])->toBe('Published echo instructions.');
});

it('exposes the registry through the manager and facade', function () {
    expect(app(Cortex::class)->servers())->toBe(app(McpServerRegistry::class))
        ->and(JayI\Cortex\Facades\Cortex::servers())->toBe(app(McpServerRegistry::class));
});
