<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\Tool\Exceptions\ToolNotFoundException;
use RefactorCircus\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

beforeEach(function (): void {
    config()->set('cortex.tool_tags.namespaces', []);
});

it('tags a tool with the tags given at registration, kebab-cased and sorted', function (): void {
    $registry = app(ToolRegistry::class);

    $registry->register('echo', EchoTool::class, ['Orders', ' billing ', 'orders', 'ProductLines']);

    expect($registry->tagsFor('echo'))->toBe(['billing', 'orders', 'product-lines']);
});

it('tags config entries given as arrays', function (): void {
    config()->set('cortex.tools', [
        'echo' => ['class' => EchoTool::class, 'tags' => ['orders']],
        ['class' => EchoMcpTool::class, 'tags' => ['catalog']],
        'plain' => EchoTool::class,
    ]);

    $registry = app(ToolRegistry::class);

    expect($registry->tagsFor('echo'))->toBe(['orders'])
        ->and($registry->tagsFor(app(EchoMcpTool::class)->name()))->toBe(['catalog'])
        ->and($registry->tagsFor('plain'))->toBe([]);
});

it('derives tags from namespace patterns', function (): void {
    config()->set('cortex.tool_tags.namespaces', ['RefactorCircus\\Cortex\\{tag}\\', 'App\\Domains\\{tag}\\']);

    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class, ['orders']);

    // RefactorCircus\Cortex\Tests\Fixtures\EchoTool matches the first pattern only.
    expect($registry->tagsFor('echo'))->toBe(['orders', 'tests']);
});

it('lists every tag in use and filters tools by one', function (): void {
    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class, ['orders', 'billing']);
    $registry->register('echo-mcp', EchoMcpTool::class, ['catalog']);

    expect($registry->tags())->toBe(['billing', 'catalog', 'orders'])
        ->and(array_column($registry->all('orders'), 'name'))->toBe(['echo'])
        ->and(array_column($registry->all(), 'tags'))->toBe([['billing', 'orders'], ['catalog']])
        ->and($registry->all('missing'))->toBe([]);
});

it('throws for the tags of an unknown tool', function (): void {
    app(ToolRegistry::class)->tagsFor('missing');
})->throws(ToolNotFoundException::class);

it('filters the tools API and MCP tool by tag', function (): void {
    app(ToolRegistry::class)->register('echo', EchoTool::class, ['orders']);
    app(ToolRegistry::class)->register('echo-mcp', EchoMcpTool::class, ['catalog']);

    $http = $this->getJson(route('cortex.tools.index', ['tag' => 'orders']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'echo')
        ->assertJsonPath('data.0.tags', ['orders'])
        ->json('data');

    mcpTool(ListToolsTool::class, ['tag' => 'orders'])
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});
