<?php

declare(strict_types=1);

use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentTool;
use JayI\Cortex\Tests\Fixtures\EchoMcpTool;
use JayI\Cortex\Tests\Fixtures\EchoRequestTool;
use Laravel\Ai\Tools\Request;

it('gives a tool its own request class, filled, when an agent calls it', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo-request', EchoRequestTool::class);

    expect((string) $registry->get('echo-request')->handle(new Request(['message' => 'hello'])))
        ->toBe('hello');
});

it('still fills the base request when an agent calls a tool', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo-mcp', EchoMcpTool::class);

    expect((string) $registry->get('echo-mcp')->handle(new Request(['message' => 'hi'])))
        ->toBe('hi');
});

it('lets agents call the package tools with their arguments', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('show-agent', ShowVirtualAgentTool::class);

    // Before the fix the request arrived empty and failed validation on
    // `slug`; with it, the tool looks the slug up and reports it missing.
    expect((string) $registry->get('show-agent')->handle(new Request(['slug' => 'missing-agent'])))
        ->toContain('Not found.');
});

it('does not leak one call\'s arguments into the next', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo-request', EchoRequestTool::class);
    $tool = $registry->get('echo-request');

    $tool->handle(new Request(['message' => 'first']));

    expect((string) $tool->handle(new Request(['message' => 'second'])))->toBe('second')
        ->and(app()->bound(Laravel\Mcp\Request::class))->toBeFalse();
});
