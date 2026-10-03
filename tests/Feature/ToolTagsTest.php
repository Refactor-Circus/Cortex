<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Exceptions\ToolNotFoundException;
use JayI\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoMcpTool;
use JayI\Cortex\Tests\Fixtures\EchoTool;

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
    config()->set('cortex.tool_tags.namespaces', ['JayI\\Cortex\\{tag}\\', 'App\\Domains\\{tag}\\']);

    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class, ['orders']);

    // JayI\Cortex\Tests\Fixtures\EchoTool matches the first pattern only.
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

describe('dashboard', function (): void {
    beforeEach(function (): void {
        ValidateCsrfToken::except(['*']);

        app()->detectEnvironment(fn (): string => 'local');

        app(ToolRegistry::class)->register('echo', EchoTool::class, ['orders']);
        app(ToolRegistry::class)->register('echo-mcp', EchoMcpTool::class, ['catalog']);
    });

    it('shows tool tags and filters the tool list by one', function (): void {
        $this->get(route('atrium.cortex.tools.index'))
            ->assertOk()
            ->assertSee('data-testid="tool-tags"', false)
            ->assertSee(route('atrium.cortex.tools.index', ['tag' => 'catalog']), false)
            ->assertSee('echo-mcp');

        $this->get(route('atrium.cortex.tools.index', ['tag' => 'orders']))
            ->assertOk()
            ->assertSee(route('atrium.cortex.tools.description', 'echo'), false)
            ->assertDontSee(route('atrium.cortex.tools.description', 'echo-mcp'), false);

        $this->get(route('atrium.cortex.tools.index', ['tag' => 'nothing']))
            ->assertOk()
            ->assertSee(__('cortex::cortex.no_tools_tagged', ['tag' => 'nothing']));
    });

    it('offers tag filters in the virtual agent tool picker', function (): void {
        VirtualAgentModel::factory()->published()->create(['slug' => 'helper', 'tools' => ['echo']]);

        $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
            ->assertOk()
            ->assertSee('data-testid="tool-picker"', false)
            ->assertSee('data-tags="[&quot;catalog&quot;]"', false)
            ->assertSee('value="echo"', false);

        $this->get(route('atrium.cortex.virtual-agents.create'))
            ->assertOk()
            ->assertSee('data-testid="tool-picker"', false);
    });

    it('offers tag filters in the concrete agent tool picker', function (): void {
        app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);

        $this->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
            ->assertOk()
            ->assertSee('data-testid="tool-picker"', false)
            ->assertSee('data-tags="[&quot;orders&quot;]"', false);
    });
});
