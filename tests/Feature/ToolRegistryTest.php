<?php

declare(strict_types=1);

use JayI\Cortex\Cortex;
use JayI\Cortex\Domains\Tool\Actions\ListToolsAction;
use JayI\Cortex\Domains\Tool\Exceptions\ToolNotFoundException;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Tests\Fixtures\EchoMcpTool;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use Laravel\Ai\Tools\McpServerTool;

it('registers and resolves tools at runtime', function () {
    $registry = app(ToolRegistry::class);

    $registry->register('echo', EchoTool::class);

    expect($registry->has('echo'))->toBeTrue()
        ->and($registry->get('echo'))->toBeInstanceOf(EchoTool::class)
        ->and($registry->names())->toBe(['echo']);
});

it('loads tools from the package config', function () {
    config()->set('cortex.tools', ['echo' => EchoTool::class]);

    expect(app(ToolRegistry::class)->has('echo'))->toBeTrue();
});

it('rejects classes that are not tools', function () {
    app(ToolRegistry::class)->register('bad', stdClass::class);
})->throws(InvalidArgumentException::class, 'must implement');

it('throws for unknown tool names', function () {
    app(ToolRegistry::class)->get('missing');
})->throws(ToolNotFoundException::class, 'Tool [missing] is not registered.');

it('describes registered tools with serialized schemas', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class);

    $tools = app(ListToolsAction::class)->execute();

    expect($tools)->toHaveCount(1)
        ->and($tools[0]['name'])->toBe('echo')
        ->and($tools[0]['class'])->toBe(EchoTool::class)
        ->and($tools[0]['description'])->toBe('Echoes back the given message.')
        ->and($tools[0]['schema']['type'])->toBe('object')
        ->and($tools[0]['schema']['properties'])->toHaveKey('message')
        ->and($tools[0]['schema']['required'])->toBe(['message']);
});

it('registers Laravel MCP tools and resolves them wrapped for agent use', function () {
    $registry = app(ToolRegistry::class);

    $registry->register('echo-mcp', EchoMcpTool::class);

    $tool = $registry->get('echo-mcp');

    expect($tool)->toBeInstanceOf(McpServerTool::class)
        ->and((string) $tool->description())->toBe('Echoes back the given message over MCP.');
});

it('derives names for unkeyed config entries from the tool itself', function () {
    config()->set('cortex.tools', [EchoMcpTool::class]);

    expect(app(ToolRegistry::class)->names())->toBe(['echo-mcp-tool']);
});

it('describes registered MCP tools with serialized schemas', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo-mcp', EchoMcpTool::class);

    $tools = app(ListToolsAction::class)->execute();

    expect($tools)->toHaveCount(1)
        ->and($tools[0]['name'])->toBe('echo-mcp')
        ->and($tools[0]['class'])->toBe(EchoMcpTool::class)
        ->and($tools[0]['description'])->toBe('Echoes back the given message over MCP.')
        ->and($tools[0]['schema']['properties'])->toHaveKey('message')
        ->and($tools[0]['schema']['required'])->toBe(['message']);
});

it('exposes the registry through the manager and facade', function () {
    expect(app(Cortex::class)->tools())->toBe(app(ToolRegistry::class))
        ->and(JayI\Cortex\Facades\Cortex::tools())->toBe(app(ToolRegistry::class));
});
