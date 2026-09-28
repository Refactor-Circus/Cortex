<?php

declare(strict_types=1);

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Exceptions\AgentNotFoundException;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tests\Fixtures\PlainAgent;

it('registers keyed agents from the config', function () {
    config()->set('cortex.agents', ['triage' => EchoAgent::class]);

    $registry = app(AgentRegistry::class);

    expect($registry->has('triage'))->toBeTrue()
        ->and($registry->get('triage'))->toBe(EchoAgent::class)
        ->and($registry->names())->toBe(['triage']);
});

it('derives a kebab name for unkeyed config entries', function () {
    config()->set('cortex.agents', [EchoAgent::class]);

    expect(app(AgentRegistry::class)->names())->toBe(['echo-agent']);
});

it('registers agents at runtime', function () {
    $registry = app(AgentRegistry::class);
    $registry->register('plain', PlainAgent::class);

    expect($registry->has('plain'))->toBeTrue()
        ->and($registry->make('plain'))->toBeInstanceOf(PlainAgent::class);
});

it('rejects classes that are not agents', function () {
    app(AgentRegistry::class)->register('echo', EchoTool::class);
})->throws(InvalidArgumentException::class);

it('throws for unregistered names', function () {
    app(AgentRegistry::class)->get('missing');
})->throws(AgentNotFoundException::class);

it('finds the registered name for a class', function () {
    $registry = app(AgentRegistry::class);
    $registry->register('echo-agent', EchoAgent::class);

    expect($registry->nameFor(EchoAgent::class))->toBe('echo-agent')
        ->and($registry->nameFor(PlainAgent::class))->toBeNull();
});

it('reports which agents read overrides', function () {
    $registry = app(AgentRegistry::class);
    $registry->register('echo-agent', EchoAgent::class);
    $registry->register('plain', PlainAgent::class);

    expect($registry->supportsOverrides('echo-agent'))->toBeTrue()
        ->and($registry->supportsOverrides('plain'))->toBeFalse();
});

it('reads the code-declared prompt and tools', function () {
    $registry = app(AgentRegistry::class);
    $registry->register('echo-agent', EchoAgent::class);
    $registry->register('plain', PlainAgent::class);

    expect($registry->defaultInstructions('echo-agent'))->toBe('Echo everything back.')
        ->and($registry->defaultTools('echo-agent'))->toBe(['EchoTool'])
        ->and($registry->defaultInstructions('plain'))->toBe('Plain instructions.')
        ->and($registry->defaultTools('plain'))->toBe(['EchoTool']);
});

it('lists every agent with its live and default configuration', function () {
    $registry = app(AgentRegistry::class);
    $registry->register('echo-agent', EchoAgent::class);

    expect($registry->all())->toBe([[
        'name' => 'echo-agent',
        'class' => EchoAgent::class,
        'overridable' => true,
        'tools_overridable' => true,
        'instructions' => 'Echo everything back.',
        'tools' => ['EchoTool'],
        'default_tools' => ['EchoTool'],
    ]]);
});
