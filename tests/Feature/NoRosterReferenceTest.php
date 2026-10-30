<?php

declare(strict_types=1);

/**
 * Cortex knows no other suite package by name: which screens its
 * redirect-domain UI goes on is config (`cortex.atrium.redirect_domains.screens`),
 * so no file under src/ names Roster, comments included.
 */
it('names roster nowhere in its source', function (): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', FilesystemIterator::SKIP_DOTS));
    $naming = [];
    $checked = 0;

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $checked++;

        if (stripos((string) file_get_contents($file->getPathname()), 'roster') !== false) {
            $naming[] = $file->getPathname();
        }
    }

    expect($checked)->toBeGreaterThan(0)
        ->and($naming)->toBe([]);
});
