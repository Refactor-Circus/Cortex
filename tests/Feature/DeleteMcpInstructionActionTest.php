<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionDeletedEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionDeletingEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

beforeEach(function (): void {
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
});

/**
 * An override built by the usual flows: two instruction versions (the second
 * published) and, when given, a saved tool list.
 */
function deletableInstruction(?array $tools): McpInstructionModel
{
    app(CreateMcpInstructionVersionAction::class)->execute('grouped', new CreateMcpInstructionVersionData(content: 'First instructions.'));
    app(CreateMcpInstructionVersionAction::class)->execute('grouped', new CreateMcpInstructionVersionData(content: 'Second instructions.', publish: true));

    if ($tools !== null) {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData($tools));
    }

    return McpInstructionModel::query()->where('server', 'grouped')->firstOrFail();
}

function deleteInstructions(McpInstructionModel $instruction): ?McpInstructionModel
{
    $surviving = app(DeleteMcpInstructionAction::class)->execute($instruction);
    app()->forgetScopedInstances();

    return $surviving;
}

it('reports the surviving row, or null when the row went (D-057)', function (): void {
    $kept = deleteInstructions(deletableInstruction(['echo-mcp-tool']));

    expect($kept)->toBeInstanceOf(McpInstructionModel::class)
        ->and($kept?->server)->toBe('grouped')
        ->and($kept?->tools)->toBe(['echo-mcp-tool'])
        ->and($kept?->published_version_id)->toBeNull();

    McpInstructionModel::query()->delete();

    expect(deleteInstructions(deletableInstruction(null)))->toBeNull();
});

it('keeps a row holding a tool override, without its instructions', function (): void {
    $instruction = deletableInstruction(['renamed-echo', 'echo-mcp-tool']);

    app()->forgetScopedInstances();
    expect(app(McpInstructionOverrides::class)->for('grouped'))->toBe('Second instructions.');

    deleteInstructions($instruction);

    $row = McpInstructionModel::query()->where('server', 'grouped')->first();

    expect($row)->not->toBeNull()
        ->and($row?->getKey())->toBe($instruction->getKey())
        ->and($row?->published_version_id)->toBeNull()
        ->and($row?->tools)->toBe(['renamed-echo', 'echo-mcp-tool'])
        ->and(McpInstructionVersionModel::query()->count())->toBe(0)
        // The tool override still applies; the instructions come from code.
        ->and(app(McpToolOverrides::class)->for('grouped'))->toBe(['renamed-echo', 'echo-mcp-tool'])
        ->and(app(McpInstructionOverrides::class)->for('grouped'))->toBeNull()
        ->and(app(McpServerRegistry::class)->all())->toContain([
            'name' => 'grouped',
            'class' => GroupedToolsServer::class,
            'instructions' => 'Grouped tools server instructions.',
        ]);
});

it('keeps a row holding an empty tool override', function (): void {
    deleteInstructions(deletableInstruction([]));

    expect(McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()->tools)->toBe([])
        ->and(app(McpToolOverrides::class)->for('grouped'))->toBe([]);
});

it('removes a row without a tool override, with its versions', function (): void {
    deleteInstructions(deletableInstruction(null));

    expect(McpInstructionModel::query()->count())->toBe(0)
        ->and(McpInstructionVersionModel::query()->count())->toBe(0)
        ->and(app(McpInstructionOverrides::class)->for('grouped'))->toBeNull()
        ->and(app(McpToolOverrides::class)->for('grouped'))->toBeNull();
});

it('deletes the versions one model at a time, firing their model events', function (bool $keepsTools): void {
    $instruction = deletableInstruction($keepsTools ? ['echo-mcp-tool'] : null);
    $ids = $instruction->versions()->pluck('id')->sort()->values()->all();

    $deleting = [];
    $deleted = [];

    Event::listen(McpInstructionVersionDeletingEvent::class, function (McpInstructionVersionDeletingEvent $event) use (&$deleting): void {
        $deleting[] = $event->version->getKey();
    });
    Event::listen(McpInstructionVersionDeletedEvent::class, function (McpInstructionVersionDeletedEvent $event) use (&$deleted): void {
        $deleted[] = $event->version->getKey();
    });

    deleteInstructions($instruction);

    sort($deleting);
    sort($deleted);

    expect($ids)->toHaveCount(2)
        ->and($deleting)->toBe($ids)
        ->and($deleted)->toBe($ids);
})->with([
    'row kept for its tools' => [true],
    'row removed' => [false],
]);

it('audits the delete when Keen is installed', function (): void {
    app()->register(KeenServiceProvider::class);
    test()->loadMigrationsFrom(dirname(__DIR__, 2).'/vendor/refactor-circus/keen/database/migrations');

    $instruction = deletableInstruction(['echo-mcp-tool']);

    deleteInstructions($instruction);

    // Keen records action events, one entry per action; the per-version
    // deletes show as model events (above), not as entries of their own.
    $entries = AuditEntryModel::query()
        ->where('source', 'cortex')
        ->where('action', 'mcp_instruction.deleted')
        ->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()?->subject_id)->toBe($instruction->getKey());
})->skip(! class_exists(AuditEntryModel::class), 'refactor-circus/keen is not installed.');

it('keeps the row and its tools when a tools write lands after the action started', function (): void {
    $instruction = deletableInstruction(null);
    $written = false;

    // The starting event fires before the transaction, so this write lands
    // before the action's locked re-read; the action's own copy still has no tools.
    Event::listen(McpInstructionDeletingActionEvent::class, function (McpInstructionDeletingActionEvent $event) use (&$written): void {
        DB::table(Tables::name('mcp_instructions'))
            ->where('id', $event->instruction->getKey())
            ->update(['tools' => json_encode(['echo-request-tool'])]);

        $written = true;
    });

    expect($instruction->tools)->toBeNull();

    $surviving = deleteInstructions($instruction);

    $row = McpInstructionModel::query()->where('server', 'grouped')->first();

    expect($written)->toBeTrue()
        ->and($surviving?->tools)->toBe(['echo-request-tool'])
        ->and($row)->not->toBeNull()
        ->and($row?->tools)->toBe(['echo-request-tool'])
        ->and($row?->published_version_id)->toBeNull()
        ->and(McpInstructionVersionModel::query()->count())->toBe(0)
        ->and(app(McpToolOverrides::class)->for('grouped'))->toBe(['echo-request-tool']);
});

it('does nothing more when the row is already gone', function (): void {
    $instruction = deletableInstruction(null);

    McpInstructionModel::query()->whereKey($instruction->getKey())->delete();

    deleteInstructions($instruction);

    expect(McpInstructionModel::query()->count())->toBe(0);
});
