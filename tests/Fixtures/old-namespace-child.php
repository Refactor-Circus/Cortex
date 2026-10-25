<?php

declare(strict_types=1);

/*
 * Child-process fixture for tests/Feature/Compat/ClassShimsTest.php.
 *
 * It loads the package's Composer autoloader (resolved from the package root)
 * and nothing else: no Laravel application is created or booted, so only the
 * `autoload.files` shim can resolve the old `Domains\McpServer` names. It
 * prints one marker line when every check holds; any failed check throws, so
 * the process exits non-zero with the reason on stderr.
 */

use Laravel\Mcp\Server as McpServer;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasVersionedInstructions;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;
use RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions as OldHasVersionedInstructions;
use RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy as OldMcpInstructionPolicy;
use RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy as OldMcpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\McpServer\Support\Server as OldServer;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$shimmed = [
    'RefactorCircus\Cortex\Domains\McpServer\Support\Server',
    'RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions',
    'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel',
    'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel',
    'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy',
    'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy',
];

// Lazy: nothing is aliased until an old name is requested.
foreach ($shimmed as $old) {
    if (class_exists($old, false) || trait_exists($old, false)) {
        throw new RuntimeException("[{$old}] was aliased before anything requested it.");
    }
}

// An unshimmed old name, requested with no booted app, must not throw.
if (class_exists('RefactorCircus\Cortex\Domains\McpServer\Exceptions\McpServerNotFoundException')) {
    throw new RuntimeException('An unshimmed old name was aliased.');
}

abstract class ChildOfOldServer extends OldServer {}

final class ChildOfOldTrait extends McpServer
{
    use OldHasVersionedInstructions;
}

final class ChildOfOldMcpInstructionPolicy extends OldMcpInstructionPolicy {}

final class ChildOfOldMcpInstructionVersionPolicy extends OldMcpInstructionVersionPolicy {}

if (! is_subclass_of(ChildOfOldServer::class, Server::class)) {
    throw new RuntimeException('The old Support\Server does not resolve to the new class.');
}

if (! in_array(HasVersionedInstructions::class, class_uses(ChildOfOldTrait::class), true)) {
    throw new RuntimeException('The old HasVersionedInstructions does not resolve to the new trait.');
}

if (! is_subclass_of(ChildOfOldMcpInstructionPolicy::class, McpInstructionPolicy::class)) {
    throw new RuntimeException('The old McpInstructionPolicy does not resolve to the new class.');
}

if (! is_subclass_of(ChildOfOldMcpInstructionVersionPolicy::class, McpInstructionVersionPolicy::class)) {
    throw new RuntimeException('The old McpInstructionVersionPolicy does not resolve to the new class.');
}

// The two models are final, as they were before the rename, so no host class
// can extend them: request them by their old names instead.
$models = [
    'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel' => 'RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel',
    'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel' => 'RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel',
];

foreach ($models as $old => $new) {
    if (! class_exists($old) || (new ReflectionClass($old))->getName() !== $new) {
        throw new RuntimeException("[{$old}] does not resolve to [{$new}].");
    }
}

fwrite(STDOUT, 'old-namespace-child: ok'.PHP_EOL);
