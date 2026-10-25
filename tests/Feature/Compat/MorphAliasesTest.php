<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\ConcreteServerServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

const MORPH_ALIAS_OLD_INSTRUCTION = 'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel';
const MORPH_ALIAS_OLD_VERSION = 'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel';

/**
 * A host policy that refuses every instruction override ability.
 */
final class MorphAliasDenyingMcpInstructionPolicy
{
    public function view(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return false;
    }

    public function update(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return false;
    }
}

/**
 * The two morph keys Cortex keeps: the old class name of each moved model,
 * mapped to its new class.
 *
 * @return array<string, class-string>
 */
function morphAliasMap(): array
{
    return [
        MORPH_ALIAS_OLD_INSTRUCTION => McpInstructionModel::class,
        MORPH_ALIAS_OLD_VERSION => McpInstructionVersionModel::class,
    ];
}

function morphAliasInstruction(): McpInstructionModel
{
    $instruction = McpInstructionModel::query()->create(['server' => 'echo']);
    $instruction->versions()->create(['version' => 1, 'content' => 'Stored instructions.']);

    return $instruction;
}

/**
 * A polymorphic row written before the rename: its type column holds the
 * old class name as a raw string.
 */
function morphAliasLegacyRow(string $type, string $id): RedirectDomainModel
{
    $key = (string) Str::ulid();

    DB::table(Tables::name('redirect_domains'))->insert([
        'id' => $key,
        'domain' => 'legacy-'.strtolower($key).'.example.com',
        'owner_type' => $type,
        'owner_id' => $id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return RedirectDomainModel::query()->findOrFail($key);
}

function morphAliasProvider(): ConcreteServerServiceProvider
{
    $provider = app()->getProvider(ConcreteServerServiceProvider::class);

    expect($provider)->toBeInstanceOf(ConcreteServerServiceProvider::class);

    /** @var ConcreteServerServiceProvider $provider */
    return $provider;
}

/**
 * Boot Keen inside the running app and create its tables, so the audit
 * log records Cortex's actions.
 */
function morphAliasBootKeen(): void
{
    app()->register(KeenServiceProvider::class);

    test()->loadMigrationsFrom(dirname(__DIR__, 3).'/vendor/refactor-circus/keen/database/migrations');
}

beforeEach(function () {
    $this->morphMapBefore = Relation::$morphMap;

    ConcreteServerServiceProvider::resetMorphAliasCheck();
});

afterEach(function () {
    Relation::$morphMap = $this->morphMapBefore;

    ConcreteServerServiceProvider::resetMorphAliasCheck();
});

it('maps both old class names to the new models in both directions', function (string $old, string $new) {
    expect(Relation::getMorphedModel($old))->toBe($new)
        ->and((new $new)->getMorphClass())->toBe($old);
})->with(fn (): array => array_map(
    fn (string $old, string $new): array => [$old, $new],
    array_keys(morphAliasMap()),
    morphAliasMap(),
));

it('registers exactly the two Cortex morph keys through the merging map', function () {
    $cortex = array_filter(
        Relation::morphMap(),
        fn (string $class): bool => str_starts_with($class, 'RefactorCircus\\Cortex\\'),
    );

    expect($cortex)->toBe(morphAliasMap())
        ->and(Relation::requiresMorphMap())->toBeFalse();
});

it('resolves a stored polymorphic row holding the old class string to the new model', function () {
    $instruction = morphAliasInstruction();
    $version = $instruction->versions()->firstOrFail();

    $instructionOwned = morphAliasLegacyRow(MORPH_ALIAS_OLD_INSTRUCTION, $instruction->getKey());
    $versionOwned = morphAliasLegacyRow(MORPH_ALIAS_OLD_VERSION, $version->getKey());

    expect($instructionOwned->owner)->toBeInstanceOf(McpInstructionModel::class)
        ->and($instructionOwned->owner?->getKey())->toBe($instruction->getKey())
        ->and($versionOwned->owner)->toBeInstanceOf(McpInstructionVersionModel::class)
        ->and($versionOwned->owner?->getKey())->toBe($version->getKey());
});

it('keeps both Cortex keys when a host adds its own morph map entry after boot', function () {
    Relation::morphMap(['other' => User::class]);

    expect(Relation::getMorphedModel('other'))->toBe(User::class);

    foreach (morphAliasMap() as $old => $new) {
        expect(Relation::getMorphedModel($old))->toBe($new)
            ->and((new $new)->getMorphClass())->toBe($old);
    }
});

it('writes the old class string for new saves', function () {
    $instruction = morphAliasInstruction();
    $version = $instruction->versions()->firstOrFail();

    $instructionOwned = RedirectDomainModel::query()->make(['domain' => 'instruction.example.com']);
    $instructionOwned->owner()->associate($instruction)->save();

    $versionOwned = RedirectDomainModel::query()->make(['domain' => 'version.example.com']);
    $versionOwned->owner()->associate($version)->save();

    $stored = DB::table(Tables::name('redirect_domains'))->pluck('owner_type', 'domain')->all();

    expect($stored)->toBe([
        'instruction.example.com' => MORPH_ALIAS_OLD_INSTRUCTION,
        'version.example.com' => MORPH_ALIAS_OLD_VERSION,
    ])
        ->and($instructionOwned->fresh()?->owner)->toBeInstanceOf(McpInstructionModel::class)
        ->and($versionOwned->fresh()?->owner)->toBeInstanceOf(McpInstructionVersionModel::class);
});

it('logs nothing when both old keys resolve', function () {
    Log::spy();

    morphAliasProvider()->checkMorphAliases();

    Log::shouldNotHaveReceived('warning');
});

it('logs one warning naming the key when an old key no longer resolves, once per process', function () {
    Log::spy();

    unset(Relation::$morphMap[MORPH_ALIAS_OLD_INSTRUCTION]);

    morphAliasProvider()->checkMorphAliases();
    morphAliasProvider()->checkMorphAliases();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, MORPH_ALIAS_OLD_INSTRUCTION)
            && ($context['key'] ?? null) === MORPH_ALIAS_OLD_INSTRUCTION);
});

it('never throws from the morph check', function () {
    Log::shouldReceive('warning')->andThrow(new RuntimeException('The log is down.'));

    unset(Relation::$morphMap[MORPH_ALIAS_OLD_VERSION]);

    morphAliasProvider()->checkMorphAliases();

    expect(true)->toBeTrue();
});

it('warns when a new model is stored under another alias than its old class name', function () {
    Log::spy();

    // A later alias for the same class wins for writes.
    Relation::morphMap(['host-instruction' => McpInstructionModel::class]);

    morphAliasProvider()->checkMorphAliases();

    expect((new McpInstructionModel)->getMorphClass())->toBe('host-instruction');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, MORPH_ALIAS_OLD_INSTRUCTION)
            && str_contains($message, 'host-instruction')
            && ($context['key'] ?? null) === MORPH_ALIAS_OLD_INSTRUCTION);
});

it('runs the morph check from the booted callback', function () {
    Log::spy();

    // The app is booted, so the provider's booted callback runs at once:
    // the keys resolve, it logs nothing and marks the process as checked.
    morphAliasProvider()->boot();

    unset(Relation::$morphMap[MORPH_ALIAS_OLD_INSTRUCTION]);

    morphAliasProvider()->checkMorphAliases();

    Log::shouldNotHaveReceived('warning');

    ConcreteServerServiceProvider::resetMorphAliasCheck();

    morphAliasProvider()->checkMorphAliases();

    Log::shouldHaveReceived('warning')->once();
});

it('gives a row loaded from an old morph string the new policy', function () {
    $instruction = morphAliasInstruction();
    $loaded = morphAliasLegacyRow(MORPH_ALIAS_OLD_INSTRUCTION, $instruction->getKey())->owner;

    expect($loaded)->toBeInstanceOf(McpInstructionModel::class)
        ->and(Gate::getPolicyFor($loaded))->toBeInstanceOf(McpInstructionPolicy::class);

    Gate::policy(McpInstructionModel::class, MorphAliasDenyingMcpInstructionPolicy::class);

    expect(Gate::getPolicyFor($loaded))->toBeInstanceOf(MorphAliasDenyingMcpInstructionPolicy::class);

    Gate::forUser(new GenericUser(['id' => 1]))->authorize('update', $loaded);
})->throws(AuthorizationException::class);

it('resolves a stored audit entry holding the old class string to the new model', function () {
    morphAliasBootKeen();

    $instruction = morphAliasInstruction();

    DB::table((new AuditEntryModel)->getTable())->insert([
        'source' => 'cortex',
        'action' => 'mcp_instruction_version.published',
        'subject_type' => MORPH_ALIAS_OLD_INSTRUCTION,
        'subject_id' => $instruction->getKey(),
        'surface' => 'code',
        'hash' => str_repeat('0', 64),
        'created_at' => now(),
    ]);

    $entry = AuditEntryModel::query()->where('subject_type', MORPH_ALIAS_OLD_INSTRUCTION)->firstOrFail();

    expect($entry->subject)->toBeInstanceOf(McpInstructionModel::class)
        ->and($entry->subject?->getKey())->toBe($instruction->getKey());
})->skip(! class_exists(AuditEntryModel::class), 'refactor-circus/keen is not installed.');

it('writes the old class string on audit entries for new saves', function () {
    morphAliasBootKeen();

    $instruction = morphAliasInstruction();

    app(PublishMcpInstructionVersionAction::class)->execute($instruction, 1);

    $entry = AuditEntryModel::query()
        ->where('subject_id', $instruction->getKey())
        ->latest('id')
        ->firstOrFail();

    expect($entry->subject_type)->toBe(MORPH_ALIAS_OLD_INSTRUCTION)
        ->and($entry->subject)->toBeInstanceOf(McpInstructionModel::class)
        ->and($entry->subject?->getKey())->toBe($instruction->getKey());
})->skip(! class_exists(AuditEntryModel::class), 'refactor-circus/keen is not installed.');
