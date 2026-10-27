<?php

declare(strict_types=1);

/*
 * AC 2 (D-040): the old domain namespace may appear only in the compatibility
 * files that exist to name it. The allowlist is a fixed set of named files,
 * asserted exactly (members and count), so a moved test cannot keep an old
 * import and src cannot slip back to the old namespace without editing this
 * test.
 *
 * This file never spells the old name itself: the pattern below puts `+`
 * between the backslashes and `McpServer`, so it does not match its own text.
 */

/**
 * Matches the old namespace with one or more backslashes between the two
 * segments, so both `Domains\X` in a docblock or `use` line and the escaped
 * `Domains\\X` form in a PHP string are found. Class names ignore case.
 */
const OLD_NAMESPACE_PATTERN = '/Domains\\\\+McpServer\b/i';

/**
 * The files allowed to name the old namespace, relative to the package root.
 *
 * @var list<string>
 */
const OLD_NAMESPACE_ALLOWLIST = [
    // Group 2: the shim file and its tests and fixtures, including the subprocess child.
    'src/Domains/ConcreteServer/shims.php',
    'tests/Feature/Compat/ClassShimsTest.php',
    'tests/Fixtures/OldNamespaceServer.php',
    'tests/Fixtures/OldTraitServer.php',
    'tests/Fixtures/old-namespace-child.php',
    // Groups 10 and 11: the morph aliases and the old policy keys (the old model names in the provider).
    'src/Domains/ConcreteServer/ConcreteServerServiceProvider.php',
    // Group 10: the morph-alias and old-row tests.
    'tests/Feature/Compat/MorphAliasesTest.php',
    // Group 11: the old policy-key test and its host policy fixture.
    'tests/Feature/Compat/OldPolicyKeysTest.php',
    'tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php',
];

/**
 * Every file under the given folders of $root whose contents name the old
 * namespace, as sorted root-relative paths with forward slashes.
 *
 * @param  list<string>  $folders
 * @return list<string>
 */
function oldNamespaceReferences(string $root, array $folders): array
{
    $found = [];

    foreach ($folders as $folder) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.DIRECTORY_SEPARATOR.$folder, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (preg_match(OLD_NAMESPACE_PATTERN, (string) file_get_contents($file->getPathname())) === 1) {
                $relative = substr($file->getPathname(), strlen($root) + 1);

                $found[] = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            }
        }
    }

    sort($found);

    return $found;
}

it('keeps the allowlist to its nine named files', function (): void {
    $root = dirname(__DIR__, 3);

    expect(OLD_NAMESPACE_ALLOWLIST)->toHaveCount(9)
        ->and(array_unique(OLD_NAMESPACE_ALLOWLIST))->toHaveCount(9);

    foreach (OLD_NAMESPACE_ALLOWLIST as $path) {
        expect(is_file($root.'/'.$path))->toBeTrue("Allowlisted file {$path} does not exist.");
    }
});

it('names the old namespace only in the allowlisted files of src and tests', function (): void {
    $root = dirname(__DIR__, 3);

    $allowed = OLD_NAMESPACE_ALLOWLIST;
    sort($allowed);

    $found = oldNamespaceReferences($root, ['src', 'tests']);

    expect(array_values(array_diff($found, $allowed)))->toBe([], 'These files name the old Domains namespace but are not compatibility files: move them to ConcreteServer.')
        ->and(array_values(array_diff($allowed, $found)))->toBe([], 'These allowlisted files no longer name the old namespace: take them off the allowlist and lower its count.')
        ->and($found)->toBe($allowed);
});

it('finds the old namespace in both its plain and escaped forms', function (): void {
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cortex-old-namespace-'.bin2hex(random_bytes(6));
    $nested = $root.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Nested';

    mkdir($nested, recursive: true);

    $old = 'RefactorCircus\Cortex\Domains'.'\\'.'McpServer';

    file_put_contents($root.'/src/Plain.php', "<?php\n\nuse {$old}\\Support\\Server;\n");
    file_put_contents($nested.'/Escaped.php', "<?php\n\n\$name = '".str_replace('\\', '\\\\', $old)."\\\\Models\\\\McpInstructionModel';\n");
    file_put_contents($root.'/src/Clean.php', "<?php\n\nuse RefactorCircus\\Cortex\\Domains\\ConcreteServer\\Support\\Server;\n");

    try {
        expect(oldNamespaceReferences($root, ['src']))->toBe(['src/Nested/Escaped.php', 'src/Plain.php']);
    } finally {
        foreach (['/src/Plain.php', '/src/Clean.php', '/src/Nested/Escaped.php'] as $file) {
            unlink($root.$file);
        }

        rmdir($nested);
        rmdir($root.'/src');
        rmdir($root);
    }
});
