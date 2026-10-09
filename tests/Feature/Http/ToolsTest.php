<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

it('lists registered tools with schemas', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);

    $this->getJson(route('cortex.tools.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'echo')
        ->assertJsonPath('data.0.description', 'Echoes back the given message.')
        ->assertJsonPath('data.0.schema.type', 'object')
        ->assertJsonPath('data.0.schema.required', ['message']);
});

it('returns an empty list without registered tools', function () {
    $this->getJson(route('cortex.tools.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
