<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Methods\CallTool;
use Laravel\Mcp\Server\Methods\ListTools;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerContextRebuilder;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoRequestTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\NamedMcpTool;
use RefactorCircus\Cortex\Tests\Support\ServerContextTools;

/*
 * Vendor pin (D-025, D-051): serving a tool override rebuilds laravel/mcp's
 * ServerContext from its constructor and reads its protected $tools. These
 * tests fail on a laravel/mcp change that breaks either, or that stops
 * tools/list from keeping the order of the context's tools.
 */

/**
 * @return list<string>
 */
function pinnedListedNames(ServerContext $context): array
{
    $response = (new ListTools)->handle(new JsonRpcRequest(1, 'tools/list', []), $context);

    /** @var list<string> */
    return array_column($response->toArray()['result']['tools'], 'name');
}

it('pins the ServerContext constructor the rebuild passes, each parameter backed by its property', function (): void {
    $parameters = (new ReflectionMethod(ServerContext::class, '__construct'))->getParameters();

    expect(array_map(fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters))->toBe([
        'supportedProtocolVersions',
        'serverCapabilities',
        'implementation',
        'instructions',
        'maxPaginationLength',
        'defaultPaginationLength',
        'tools',
        'resources',
        'prompts',
    ]);

    foreach ($parameters as $parameter) {
        expect($parameter->isPromoted())->toBeTrue("[{$parameter->getName()}] is no longer a promoted property.")
            ->and($parameter->isOptional())->toBeFalse();
    }
});

it('pins the protected tools property the rebuild reads', function (): void {
    $property = new ReflectionProperty(ServerContext::class, 'tools');

    expect($property->isProtected())->toBeTrue()
        ->and($property->isReadOnly())->toBeFalse()
        ->and((string) $property->getType())->toBe('array')
        // The instructions override assigns this one directly.
        ->and((new ReflectionProperty(ServerContext::class, 'instructions'))->isPublic())->toBeTrue()
        ->and((new ReflectionProperty(ServerContext::class, 'instructions'))->isReadOnly())->toBeFalse();
});

it('keeps the order of the context tools in tools/list', function (array $tools, array $names): void {
    $context = (new EchoServer(new FakeTransporter))->createContext();

    $rebuilt = app(ServerContextRebuilder::class)->withTools($context, array_map(fn (string $class): Tool => app($class), $tools));

    expect(ServerContextTools::names($rebuilt))->toBe($names)
        ->and(pinnedListedNames($rebuilt))->toBe($names);
})->with([
    'declared order' => [[NamedMcpTool::class, EchoMcpTool::class, EchoRequestTool::class], ['renamed-echo', 'echo-mcp-tool', 'echo-request-tool']],
    'reversed' => [[EchoRequestTool::class, EchoMcpTool::class, NamedMcpTool::class], ['echo-request-tool', 'echo-mcp-tool', 'renamed-echo']],
]);

it('reads the tools a context was built with through the shared helper', function (): void {
    $context = (new GroupedToolsServer(new FakeTransporter))->createContext();

    expect(ServerContextTools::of($context))->toBe([
        EchoMcpTool::class,
        ToolSearch::class => [EchoRequestTool::class, NamedMcpTool::class],
    ])
        ->and(ServerContextTools::of($context))->toBe(app(ServerContextRebuilder::class)->tools($context));
});

it('rebuilds a context from a real server that keeps everything but its tools and serves it end to end', function (): void {
    $context = (new GroupedToolsServer(new FakeTransporter))->createContext();
    $context->instructions = 'Rebuilt instructions.';

    $rebuilt = app(ServerContextRebuilder::class)->withTools($context, [app(NamedMcpTool::class)]);

    expect($rebuilt)->not->toBe($context)
        ->and($rebuilt->instructions)->toBe('Rebuilt instructions.')
        ->and($rebuilt->implementation)->toBe($context->implementation)
        ->and($rebuilt->supportedProtocolVersions)->toBe($context->supportedProtocolVersions)
        ->and($rebuilt->serverCapabilities)->toBe($context->serverCapabilities)
        ->and($rebuilt->maxPaginationLength)->toBe($context->maxPaginationLength)
        ->and($rebuilt->defaultPaginationLength)->toBe($context->defaultPaginationLength)
        ->and($rebuilt->resources()->all())->toBe($context->resources()->all())
        ->and($rebuilt->prompts()->all())->toBe($context->prompts()->all())
        ->and(pinnedListedNames($rebuilt))->toBe(['renamed-echo'])
        // The source context is left as it was.
        ->and(pinnedListedNames($context))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools']);

    $call = new JsonRpcRequest(2, 'tools/call', [
        'name' => 'renamed-echo',
        'arguments' => ['message' => 'pinned'],
    ]);

    // As Server::runMethodHandle() does around every method.
    app()->instance('mcp.request', $call->toRequest());

    try {
        $called = (new CallTool)->handle($call, $rebuilt);
    } finally {
        app()->forgetInstance('mcp.request');
    }

    expect($called)->toBeInstanceOf(JsonRpcResponse::class)
        ->and($called->toArray()['result']['content'][0]['text'])->toBe('pinned');
});
