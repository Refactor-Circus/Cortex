<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsClearedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsClearingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsUpdatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsUpdatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Exceptions\McpServerNotFoundException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoUpdateToolsMcpInstructionPolicy;

beforeEach(function (): void {
    // Code tools of `grouped`: echo-mcp-tool, echo-request-tool, renamed-echo.
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(McpServerRegistry::class)->register('echo', EchoServer::class);

    app(ToolRegistry::class)->register('echo-cortex', EchoCortexTool::class);
    app(ToolRegistry::class)->register('echo-ai', EchoTool::class);
});

/**
 * The validation errors for a tools update on the server, keyed by field;
 * empty when the input passes.
 *
 * @param  array<string, mixed>  $input
 * @return array<string, array<int, string>>
 */
function serverToolsErrors(array $input, string $server = 'grouped'): array
{
    try {
        UpdateConcreteServerToolsData::from($input, $server);
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

function updateServerTools(string $server, ?array $tools): McpInstructionModel
{
    return app(UpdateConcreteServerToolsAction::class)->execute($server, new UpdateConcreteServerToolsData($tools));
}

function clearServerTools(string $server): void
{
    app(ClearConcreteServerToolsAction::class)->execute($server, new ClearConcreteServerToolsData);
}

/**
 * An override row for the server holding published instructions, with the
 * given tool list.
 */
function serverWithInstructions(string $server, ?array $tools): McpInstructionModel
{
    $instruction = McpInstructionModel::query()->create(['server' => $server, 'tools' => $tools]);
    $version = $instruction->versions()->create(['version' => 1, 'content' => "Published {$server} instructions."]);
    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    return $instruction->refresh();
}

describe('update rules', function (): void {
    it('requires the tools key, which may be null or a list', function (): void {
        expect(serverToolsErrors([]))->toHaveKey('tools')
            ->and(serverToolsErrors(['tools' => 'echo-mcp-tool']))->toHaveKey('tools')
            ->and(serverToolsErrors(['tools' => ['first' => 'echo-mcp-tool']]))->toHaveKey('tools')
            ->and(serverToolsErrors(['tools' => null]))->toBe([])
            ->and(serverToolsErrors(['tools' => []]))->toBe([]);
    });

    it('accepts the server code tools and registered Cortex tools', function (): void {
        expect(serverToolsErrors(['tools' => ['renamed-echo', 'echo-cortex', 'echo-mcp-tool', 'echo-ai']]))->toBe([]);
    });

    it('names code tools by name(), not by class basename', function (): void {
        expect(serverToolsErrors(['tools' => ['named-mcp-tool']]))->toHaveKey('tools.0');
    });

    it('refuses an unknown entry on its own index', function (): void {
        $errors = serverToolsErrors(['tools' => ['echo-mcp-tool', 'missing-tool']]);

        expect(array_keys($errors))->toBe(['tools.1']);
    });

    it('refuses an entry that is not a string', function (): void {
        expect(serverToolsErrors(['tools' => [42]]))->toHaveKey('tools.0');
    });

    it('refuses the same entry twice', function (): void {
        expect(serverToolsErrors(['tools' => ['echo-mcp-tool', 'echo-mcp-tool']]))->toHaveKey('tools.1');
    });

    it('refuses the later of two entries served under the same name', function (): void {
        // A registered tool reusing the code tool's class is served as echo-mcp-tool too.
        app(ToolRegistry::class)->register('echo-alias', EchoMcpTool::class);

        expect(array_keys(serverToolsErrors(['tools' => ['echo-mcp-tool', 'echo-alias']])))->toBe(['tools.1'])
            ->and(array_keys(serverToolsErrors(['tools' => ['echo-alias', 'renamed-echo', 'echo-mcp-tool']])))->toBe(['tools.2']);
    });

    it('refuses the later of two registered entries served under the same name', function (): void {
        app(ToolRegistry::class)->register('echo-cortex-again', EchoCortexTool::class);

        expect(array_keys(serverToolsErrors(['tools' => ['echo-cortex', 'echo-cortex-again']], 'echo')))->toBe(['tools.1']);
    });

    it('accepts an entry matching both a code tool and a registered tool, as the code tool', function (): void {
        // D-021: no rejection and no qualified syntax; serving picks the code tool.
        app(ToolRegistry::class)->register('echo-mcp-tool', EchoCortexTool::class);

        expect(serverToolsErrors(['tools' => ['echo-mcp-tool', 'echo-cortex']]))->toBe([]);
    });

    it('offers a server without code tools the registered tools only', function (): void {
        expect(serverToolsErrors(['tools' => ['echo-cortex']], 'echo'))->toBe([])
            ->and(serverToolsErrors(['tools' => ['echo-mcp-tool']], 'echo'))->toHaveKey('tools.0');
    });

    it('names an unknown server', function (): void {
        UpdateConcreteServerToolsData::rules('missing');
    })->throws(McpServerNotFoundException::class);
});

describe('update action', function (): void {
    it('creates the override row with the list in stored order', function (): void {
        $instruction = updateServerTools('grouped', ['renamed-echo', 'echo-cortex', 'echo-mcp-tool']);

        expect($instruction->exists)->toBeTrue()
            ->and($instruction->server)->toBe('grouped')
            ->and($instruction->tools)->toBe(['renamed-echo', 'echo-cortex', 'echo-mcp-tool'])
            ->and($instruction->published_version_id)->toBeNull()
            ->and(McpInstructionModel::query()->where('server', 'grouped')->count())->toBe(1);
    });

    it('updates the existing row and keeps its instructions', function (): void {
        $existing = serverWithInstructions('grouped', ['echo-mcp-tool']);

        $instruction = updateServerTools('grouped', ['renamed-echo']);

        expect($instruction->getKey())->toBe($existing->getKey())
            ->and($instruction->tools)->toBe(['renamed-echo'])
            ->and($instruction->published_version_id)->toBe($existing->published_version_id)
            ->and($instruction->versions()->count())->toBe(1)
            ->and(McpInstructionModel::query()->count())->toBe(1);
    });

    it('stores an empty list as an empty list, apart from null', function (): void {
        updateServerTools('grouped', []);

        $raw = DB::table(Tables::name('mcp_instructions'))->where('server', 'grouped')->value('tools');

        expect($raw)->toBe('[]')
            ->and(McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()->tools)->toBe([]);

        updateServerTools('grouped', null);

        expect(DB::table(Tables::name('mcp_instructions'))->where('server', 'grouped')->value('tools'))->toBeNull();
    });

    it('decides create or update from the row read inside its transaction', function (): void {
        // A row that appears after the action started (and before its locked
        // re-read) is updated, never duplicated or overwritten.
        $created = false;

        Event::listen(ConcreteServerToolsUpdatingActionEvent::class, function () use (&$created): void {
            serverWithInstructions('grouped', null);
            $created = true;
        });

        $instruction = updateServerTools('grouped', ['echo-mcp-tool']);

        expect($created)->toBeTrue()
            ->and(McpInstructionModel::query()->where('server', 'grouped')->count())->toBe(1)
            ->and($instruction->tools)->toBe(['echo-mcp-tool'])
            ->and($instruction->published_version_id)->not->toBeNull()
            ->and($instruction->versions()->count())->toBe(1);
    });

    it('refuses the cortex server with a 422 on the server', function (): void {
        try {
            updateServerTools('cortex', []);
        } catch (ValidationException $exception) {
            expect($exception->status)->toBe(422)
                ->and($exception->errors())->toHaveKey('server')
                ->and(McpInstructionModel::query()->count())->toBe(0);

            return;
        }

        $this->fail('The cortex server tool list was overridden.');
    });

    it('lets a CortexServer registered under another name be overridden', function (): void {
        app(McpServerRegistry::class)->register('cortex-copy', CortexServer::class);

        expect(updateServerTools('cortex-copy', [])->tools)->toBe([]);
    });

    it('refuses an unknown server', function (): void {
        updateServerTools('missing', []);
    })->throws(McpServerNotFoundException::class);

    it('announces the update with its starting and finished events', function (): void {
        Event::fake([ConcreteServerToolsUpdatingActionEvent::class, ConcreteServerToolsUpdatedActionEvent::class]);

        $data = new UpdateConcreteServerToolsData(['echo-mcp-tool']);
        $instruction = app(UpdateConcreteServerToolsAction::class)->execute('grouped', $data);

        Event::assertDispatched(ConcreteServerToolsUpdatingActionEvent::class, fn ($event): bool => $event->server === 'grouped' && $event->data === $data);
        Event::assertDispatched(ConcreteServerToolsUpdatedActionEvent::class, fn ($event): bool => $event->instruction->is($instruction));
    });
});

describe('updateTools ability', function (): void {
    it('is allowed by the bundled policy, for guests too', function (): void {
        $instruction = new McpInstructionModel(['server' => 'grouped']);

        expect(Gate::forUser(null)->allows('updateTools', $instruction))->toBeTrue()
            ->and(Gate::forUser(new GenericUser(['id' => 1]))->allows('updateTools', $instruction))->toBeTrue();
    });

    it('is its own ability, apart from update', function (): void {
        config()->set('cortex.policies.'.McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);
        Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);

        $instruction = new McpInstructionModel(['server' => 'grouped']);

        // A host policy without updateTools falls to the Gate default: denied.
        foreach ([null, new GenericUser(['id' => 1])] as $user) {
            expect(Gate::forUser($user)->allows('update', $instruction))->toBeTrue()
                ->and(Gate::forUser($user)->allows('updateTools', $instruction))->toBeFalse();
        }
    });
});

describe('clear action', function (): void {
    it('sets tools to null and keeps the instructions and versions', function (): void {
        $existing = serverWithInstructions('grouped', ['echo-mcp-tool']);
        $existing->versions()->create(['version' => 2, 'content' => 'Draft.']);

        clearServerTools('grouped');

        $instruction = McpInstructionModel::query()->where('server', 'grouped')->firstOrFail();

        expect($instruction->getKey())->toBe($existing->getKey())
            ->and($instruction->tools)->toBeNull()
            ->and($instruction->published_version_id)->toBe($existing->published_version_id)
            ->and($instruction->versions()->pluck('version')->sort()->values()->all())->toBe([1, 2]);
    });

    it('removes a tool-only row', function (): void {
        updateServerTools('grouped', ['echo-mcp-tool']);

        clearServerTools('grouped');

        expect(McpInstructionModel::query()->where('server', 'grouped')->exists())->toBeFalse();
    });

    it('keeps the row when a version is created after the action started', function (): void {
        updateServerTools('grouped', ['echo-mcp-tool']);

        $created = false;

        Event::listen(ConcreteServerToolsClearingActionEvent::class, function () use (&$created): void {
            McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()
                ->versions()->create(['version' => 1, 'content' => 'Written meanwhile.']);
            $created = true;
        });

        clearServerTools('grouped');

        $instruction = McpInstructionModel::query()->where('server', 'grouped')->first();

        expect($created)->toBeTrue()
            ->and($instruction)->not->toBeNull()
            ->and($instruction?->tools)->toBeNull()
            ->and(McpInstructionVersionModel::query()->count())->toBe(1);
    });

    it('keeps a row whose published pointer is set', function (): void {
        $instruction = McpInstructionModel::query()->create(['server' => 'grouped', 'tools' => ['echo-mcp-tool']]);
        $instruction->published_version_id = (string) Str::ulid();
        $instruction->save();

        clearServerTools('grouped');

        expect(McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()->tools)->toBeNull();
    });

    it('does nothing for a server without a tool override', function (): void {
        clearServerTools('grouped');

        $existing = serverWithInstructions('echo', null);
        $updatedAt = $existing->updated_at?->toIso8601String();

        $this->travel(5)->minutes();

        clearServerTools('echo');

        $instruction = McpInstructionModel::query()->where('server', 'echo')->firstOrFail();

        expect(McpInstructionModel::query()->where('server', 'grouped')->exists())->toBeFalse()
            ->and($instruction->updated_at?->toIso8601String())->toBe($updatedAt)
            ->and($instruction->versions()->count())->toBe(1);
    });

    it('refuses the cortex server with a 422 on the server', function (): void {
        McpInstructionModel::query()->create(['server' => 'cortex', 'tools' => ['list-tools']]);

        try {
            clearServerTools('cortex');
        } catch (ValidationException $exception) {
            expect($exception->status)->toBe(422)
                ->and($exception->errors())->toHaveKey('server')
                ->and(McpInstructionModel::query()->where('server', 'cortex')->firstOrFail()->tools)->toBe(['list-tools']);

            return;
        }

        $this->fail('The cortex server tool list was cleared.');
    });

    it('refuses an unknown server', function (): void {
        clearServerTools('missing');
    })->throws(McpServerNotFoundException::class);

    it('takes no input', function (): void {
        expect(ClearConcreteServerToolsData::rules('grouped'))->toBe([])
            ->and(ClearConcreteServerToolsData::from([], 'grouped'))->toBeInstanceOf(ClearConcreteServerToolsData::class);
    });

    it('announces the clear with its starting and finished events', function (): void {
        updateServerTools('grouped', ['echo-mcp-tool']);

        Event::fake([ConcreteServerToolsClearingActionEvent::class, ConcreteServerToolsClearedActionEvent::class]);

        $data = new ClearConcreteServerToolsData;
        app(ClearConcreteServerToolsAction::class)->execute('grouped', $data);

        Event::assertDispatched(ConcreteServerToolsClearingActionEvent::class, fn ($event): bool => $event->server === 'grouped' && $event->data === $data);
        Event::assertDispatched(ConcreteServerToolsClearedActionEvent::class, fn ($event): bool => $event->server === 'grouped');
    });
});
