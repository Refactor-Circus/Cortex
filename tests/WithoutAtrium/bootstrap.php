<?php

declare(strict_types=1);

/*
 * Bootstrap of the WithoutAtrium suite (phpunit.without-atrium.xml): loads
 * Composer's autoloader and removes Atrium's PSR-4 prefix from it, so no
 * Atrium class can load while Cortex boots, serves its HTTP API and answers
 * MCP. It throws rather than run the suite with Atrium still reachable.
 *
 * In process this cannot see an Atrium class reached through a classmap or a
 * Composer `files` entry, so the `without-atrium` CI job, which uninstalls
 * Atrium, is the authoritative proof.
 */

use Composer\Autoload\ClassLoader;

$prefix = 'RefactorCircus\\Atrium\\';
$probe = 'RefactorCircus\\Atrium\\Atrium';

require dirname(__DIR__, 2).'/vendor/autoload.php';

// Pest loads tests/Pest.php first, which declares the default TestCase and
// with it Atrium's ResetsScreenExtensions trait; this suite never uses either.
// No Atrium class or interface may be loaded yet.
foreach ([...get_declared_classes(), ...get_declared_interfaces()] as $declared) {
    if (str_starts_with($declared, $prefix)) {
        throw new RuntimeException("[{$declared}] was loaded before the WithoutAtrium bootstrap ran.");
    }
}

foreach (ClassLoader::getRegisteredLoaders() as $loader) {
    // Only loaders that can reach Atrium matter. Others, such as the scoped,
    // classmap-authoritative loader Laravel Herd prepends from its dump.phar,
    // never resolve an Atrium class and are left alone.
    if (! array_key_exists($prefix, $loader->getPrefixesPsr4()) && $loader->findFile($probe) === false) {
        continue;
    }

    if ($loader->isClassMapAuthoritative() || $loader->getApcuPrefix() !== null) {
        throw new RuntimeException('The WithoutAtrium suite cannot run with an authoritative classmap or APCu autoloading: run `composer dump-autoload` without --classmap-authoritative or --apcu.');
    }

    foreach (array_keys($loader->getClassMap()) as $class) {
        if (str_starts_with($class, $prefix)) {
            throw new RuntimeException("[{$class}] is in Composer's classmap: run `composer dump-autoload` without --optimize.");
        }
    }

    // ClassLoader can add a prefix but not remove one.
    (function (string $prefix): void {
        unset($this->prefixDirsPsr4[$prefix], $this->prefixLengthsPsr4[$prefix[0]][$prefix]);
    })->call($loader, $prefix);

    if (array_key_exists($prefix, $loader->getPrefixesPsr4()) || $loader->findFile($probe) !== false) {
        throw new RuntimeException("Composer still resolves [{$probe}] after removing the [{$prefix}] prefix.");
    }
}
