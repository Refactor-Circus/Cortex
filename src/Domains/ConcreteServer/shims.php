<?php

declare(strict_types=1);

/*
 * Compatibility shims for the McpServer to ConcreteServer rename.
 *
 * Loaded through `autoload.files` in composer.json, so it is in place before any
 * host class names an old `Domains\McpServer` FQCN (a service provider would be
 * too late: host servers extend these classes before providers boot).
 *
 * Lazy: it only registers an autoload fallback for the old prefix. Nothing is
 * aliased, and none of the new classes (or their laravel/mcp and Eloquent
 * parents) is loaded, until a process requests an old name.
 *
 * - A shimmed old name is aliased to its new class with class_alias().
 * - Any other old name (an event, action, Data, request or exception) is not
 *   aliased: a "<old> moved to <new>" warning is logged when a Laravel logger
 *   is bound, and the load ends in the usual class-not-found.
 *
 * The fallback never throws. Each old name is handled at most once per process,
 * which guards against recursion and means the new class is autoloaded at most
 * once per name; an old name that is already declared is never aliased again.
 * With no Laravel application (no bound logger) the warning is skipped.
 *
 * @deprecated The old `Domains\McpServer` names are removed in the next major.
 */

namespace RefactorCircus\Cortex\Domains\ConcreteServer;

use Illuminate\Container\Container;
use Throwable;

(static function (): void {
    $oldPrefix = 'RefactorCircus\Cortex\Domains\McpServer\\';
    $newPrefix = 'RefactorCircus\Cortex\Domains\ConcreteServer\\';

    $aliases = [
        /** @deprecated Use the ConcreteServer Support\Server; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Support\Server' => Support\Server::class,
        /** @deprecated Use the ConcreteServer Concerns\HasVersionedInstructions; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions' => Concerns\HasVersionedInstructions::class,
        /** @deprecated Use the ConcreteServer Models\McpInstructionModel; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel' => Models\McpInstructionModel::class,
        /** @deprecated Use the ConcreteServer Models\McpInstructionVersionModel; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel' => Models\McpInstructionVersionModel::class,
        /** @deprecated Use the ConcreteServer Policies\McpInstructionPolicy; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy' => Policies\McpInstructionPolicy::class,
        /** @deprecated Use the ConcreteServer Policies\McpInstructionVersionPolicy; removed in the next major. */
        'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy' => Policies\McpInstructionVersionPolicy::class,
    ];

    // PHP class names are case-insensitive, and so is the lookup.
    $lookup = array_change_key_case($aliases);

    $declared = static fn (string $class): bool => class_exists($class, false)
        || interface_exists($class, false)
        || trait_exists($class, false)
        || enum_exists($class, false);

    $warn = static function (string $old, string $new): void {
        if (! class_exists(Container::class, false)) {
            return;
        }

        $container = Container::getInstance();

        if (! $container->bound('log')) {
            return;
        }

        $container->make('log')->warning(
            "{$old} moved to {$new}. Cortex keeps no alias for it: update the reference (see the CHANGELOG).",
            ['old' => $old, 'new' => $new],
        );
    };

    /** @var array<string, true> $requested */
    $requested = [];

    spl_autoload_register(static function (string $class) use ($oldPrefix, $newPrefix, $lookup, $declared, $warn, &$requested): void {
        if (strncasecmp($class, $oldPrefix, strlen($oldPrefix)) !== 0) {
            return;
        }

        $key = strtolower($class);

        if (isset($requested[$key])) {
            return;
        }

        $requested[$key] = true;

        try {
            $new = $lookup[$key] ?? $newPrefix.substr($class, strlen($oldPrefix));

            // The one autoload of the new name for this old name.
            if (! class_exists($new) && ! $declared($new)) {
                return;
            }

            if (! isset($lookup[$key])) {
                $warn($class, $new);

                return;
            }

            if ($declared($class)) {
                return;
            }

            class_alias($new, $class);
        } catch (Throwable) {
            // A shim must never break autoloading: the load falls through to
            // the usual class-not-found.
        }
    });
})();
