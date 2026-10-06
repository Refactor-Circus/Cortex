<?php

declare(strict_types=1);

use JayI\Atrium\Testing\AtriumStyles;

/**
 * Cortex ships no stylesheet: its screens use Atrium's components and the
 * utilities Atrium compiles, so a class Atrium lacks would silently do nothing.
 */
it('uses only atrium styles', function (): void {
    $views = dirname(__DIR__, 3).'/resources/views';

    expect(AtriumStyles::missingClasses($views))->toBe([])
        ->and(AtriumStyles::inlineStyles($views))->toBe([]);
});
