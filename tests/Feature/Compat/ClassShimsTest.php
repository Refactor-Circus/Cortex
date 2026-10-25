<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasVersionedInstructions;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Tests\Fixtures\OldNamespaceServer;
use RefactorCircus\Cortex\Tests\Fixtures\OldTraitServer;

const CORTEX_SHIM_FILE = 'src/Domains/ConcreteServer/shims.php';

/**
 * Every shimmed old name and the class it resolves to.
 *
 * @return array<string, class-string>
 */
function shimmedClassNames(): array
{
    return [
        'RefactorCircus\Cortex\Domains\McpServer\Support\Server' => Server::class,
        'RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions' => HasVersionedInstructions::class,
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel' => McpInstructionModel::class,
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel' => McpInstructionVersionModel::class,
        'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy' => McpInstructionPolicy::class,
        'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy' => McpInstructionVersionPolicy::class,
    ];
}

function publishOldNamespaceInstructions(string $server, string $content): void
{
    $instruction = McpInstructionModel::query()->create(['server' => $server]);
    $version = $instruction->versions()->create(['version' => 1, 'content' => $content]);

    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    app(PublicationCache::class)->forget(app(PublicationCache::class)->mcpInstructionsKey());

    app()->forgetScopedInstances();
}

it('applies the instructions override to a host server on the old Support\Server', function () {
    app(McpServerRegistry::class)->register('old-namespace', OldNamespaceServer::class);

    publishOldNamespaceInstructions('old-namespace', 'Override instructions for the old namespace server.');

    $context = (new OldNamespaceServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toBe('Override instructions for the old namespace server.')
        ->and(is_subclass_of(OldNamespaceServer::class, Server::class))->toBeTrue();
});

it('applies the instructions override to a host server using the old trait', function () {
    app(McpServerRegistry::class)->register('old-trait', OldTraitServer::class);

    publishOldNamespaceInstructions('old-trait', 'Override instructions for the old trait server.');

    $context = (new OldTraitServer(new FakeTransporter))->createContext();

    expect($context->instructions)->toBe('Override instructions for the old trait server.')
        ->and(class_uses(OldTraitServer::class))->toContain(HasVersionedInstructions::class);
});

it('serves code-declared instructions on old-namespace servers without an override', function () {
    app(McpServerRegistry::class)->register('old-namespace', OldNamespaceServer::class);
    app(McpServerRegistry::class)->register('old-trait', OldTraitServer::class);

    expect((new OldNamespaceServer(new FakeTransporter))->createContext()->instructions)
        ->toBe('Code-declared old namespace server instructions.')
        ->and((new OldTraitServer(new FakeTransporter))->createContext()->instructions)
        ->toBe('Code-declared old trait server instructions.');
});

it('resolves every shimmed old name to its new class', function (string $old, string $new) {
    $exists = $new === HasVersionedInstructions::class ? trait_exists($old) : class_exists($old);

    expect($exists)->toBeTrue()
        ->and((new ReflectionClass($old))->getName())->toBe($new);
})->with(fn (): array => array_map(
    fn (string $old, string $new): array => [$old, $new],
    array_keys(shimmedClassNames()),
    shimmedClassNames(),
));

it('logs where an unshimmed old name moved to and defines no alias for it', function () {
    Log::spy();

    $old = 'RefactorCircus\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction';
    $new = 'RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction';

    expect(class_exists($old))->toBeFalse()
        ->and(class_exists($old))->toBeFalse()
        ->and(class_exists($old, false))->toBeFalse()
        ->and(class_exists($new, false))->toBeTrue();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, "{$old} moved to {$new}"));
});

it('loads the old names lazily and resolves them in a process with no booted app', function () {
    $root = dirname(__DIR__, 3);

    $files = (string) file_get_contents($root.'/vendor/composer/autoload_files.php');

    expect(str_contains($files, '/'.CORTEX_SHIM_FILE))->toBeTrue(
        'vendor/composer/autoload_files.php does not list '.CORTEX_SHIM_FILE.'. The autoloader is stale: run `composer dump-autoload` and rerun the tests.',
    );

    $result = Process::path($root)->run([PHP_BINARY, $root.'/tests/Fixtures/old-namespace-child.php']);

    expect($result->exitCode())->toBe(0, $result->errorOutput())
        ->and($result->output())->toContain('old-namespace-child: ok');
});

it('lists the shim in autoload.files and marks every shim deprecated', function () {
    $root = dirname(__DIR__, 3);

    /** @var array{autoload: array{files?: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $shim = (string) file_get_contents($root.'/'.CORTEX_SHIM_FILE);

    expect($composer['autoload']['files'] ?? [])->toContain(CORTEX_SHIM_FILE)
        ->and(substr_count($shim, '@deprecated'))->toBeGreaterThanOrEqual(count(shimmedClassNames()));

    foreach (array_keys(shimmedClassNames()) as $old) {
        expect($shim)->toMatch('/@deprecated[^\/]*\*\/\s*\''.preg_quote($old, '/').'\'/');
    }
});
