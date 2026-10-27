<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\ConcreteServer\Exceptions\McpServerNotFoundException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;

// The registry needs the container (config servers, tool construction), so
// these unit tests boot Testbench; they use no database or surface.

it('lists the code tools in declaration order, flattening ToolSearch groups', function (): void {
    $registry = app(McpServerRegistry::class);
    $registry->register('grouped', GroupedToolsServer::class);

    expect($registry->defaultTools('grouped'))->toBe([
        'echo-mcp-tool',
        'echo-request-tool',
        'renamed-echo',
    ]);
});

it('names each code tool by its name(), not its class basename', function (): void {
    $registry = app(McpServerRegistry::class);
    $registry->register('grouped', GroupedToolsServer::class);

    expect($registry->defaultTools('grouped'))
        ->toContain('renamed-echo')
        ->not->toContain('named-mcp-tool');
});

it('flattens the cortex server catalog into its tool names', function (): void {
    $expected = array_map(
        fn (string $class): string => app($class)->name(),
        CortexServer::TOOLS,
    );

    expect(app(McpServerRegistry::class)->defaultTools('cortex'))->toBe(array_values($expected));
});

it('lists no code tools for a server that declares none', function (): void {
    $registry = app(McpServerRegistry::class);
    $registry->register('echo', EchoServer::class);

    expect($registry->defaultTools('echo'))->toBe([]);
});

it('refuses default tools for an unregistered server', function (): void {
    app(McpServerRegistry::class)->defaultTools('missing');
})->throws(McpServerNotFoundException::class);

it('supports tool overrides on every server but cortex', function (): void {
    $registry = app(McpServerRegistry::class);
    $registry->register('echo', EchoServer::class);
    $registry->register('grouped', GroupedToolsServer::class);

    expect($registry->supportsToolOverrides('cortex'))->toBeFalse()
        ->and($registry->supportsToolOverrides('echo'))->toBeTrue()
        ->and($registry->supportsToolOverrides('grouped'))->toBeTrue();
});

it('keys the non-overridable rule by registered name, not by server class', function (): void {
    // CortexServer is final, so a host cannot subclass it; registering the
    // class itself under another name shows the rule follows the name.
    $registry = app(McpServerRegistry::class);
    $registry->register('cortex-copy', CortexServer::class);

    expect($registry->supportsToolOverrides('cortex-copy'))->toBeTrue()
        ->and($registry->supportsToolOverrides('cortex'))->toBeFalse();
});

it('refuses the tool override check for an unregistered server', function (): void {
    app(McpServerRegistry::class)->supportsToolOverrides('missing');
})->throws(McpServerNotFoundException::class);

it('never instantiates the server class to read its tools', function (): void {
    $registry = app(McpServerRegistry::class);
    $registry->register('grouped', GroupedToolsServer::class);

    // A server needs a transport to be built; the registry must not ask the
    // container for one.
    app()->bind(GroupedToolsServer::class, fn () => throw new RuntimeException('server instantiated'));

    expect($registry->defaultTools('grouped'))->toHaveCount(3);
});
