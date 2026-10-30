<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Testing\AtriumStyles;

/**
 * Cortex ships no stylesheet: its screens use Atrium's components and the
 * utilities Atrium compiles, so a class Atrium lacks would silently do nothing.
 */
it('uses only atrium styles', function (): void {
    $views = dirname(__DIR__, 3).'/resources/views';

    expect(AtriumStyles::missingClasses($views))->toBe([])
        ->and(AtriumStyles::inlineStyles($views))->toBe([]);
});

it('checks the redirect-domain view it puts on other packages\' screens', function (): void {
    $screens = dirname(__DIR__, 3).'/resources/views/ui/screens';

    expect($screens.'/redirect-domains.blade.php')->toBeFile()
        ->and(AtriumStyles::missingClasses($screens))->toBe([])
        ->and(AtriumStyles::inlineStyles($screens))->toBe([]);

    // Built from Atrium's components only: no component namespace of its own.
    preg_match_all('/<x-([\w.-]+)::/', (string) file_get_contents($screens.'/redirect-domains.blade.php'), $namespaces);

    expect(array_values(array_unique($namespaces[1])))->toBe(['atrium']);
});
