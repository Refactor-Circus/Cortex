<?php

declare(strict_types=1);

use JayI\Atrium\Assets\StyleRegistry;
use Symfony\Component\Finder\Finder;

/**
 * Atrium ships one precompiled stylesheet built from its own views, so a
 * Tailwind class only this package uses silently does nothing. Every class
 * its Atrium screens use must exist there or in resources/css/atrium.css,
 * which the service provider adds with Atrium's style hook.
 */
it('styles every class used on the atrium screens', function (): void {
    $root = dirname(__DIR__, 3);
    $stylesheets = file_get_contents($root.'/vendor/jayi/atrium/public/atrium.css')
        .file_get_contents($root.'/resources/css/atrium.css');

    $missing = [];

    foreach ((new Finder)->files()->in($root.'/resources/views')->name('*.blade.php') as $file) {
        $contents = $file->getContents();
        $values = [];

        // Plain class lists, and the quoted class strings in @class([...])
        // and Alpine's bound :class expressions.
        preg_match_all('/(?<![:\w-])(?:class|wrapper)="([^"]*)"/', $contents, $plain);
        $values = $plain[1];

        preg_match_all('/@class\(\[(.*?)\]\)|(?:x-bind:|:)class="([^"]*)"/s', $contents, $expressions);

        foreach (array_merge($expressions[1], $expressions[2]) as $expression) {
            preg_match_all("/'([^']*)'/", $expression, $strings);
            $values = array_merge($values, $strings[1]);
        }

        foreach ($values as $value) {
            $value = (string) preg_replace('/\{\{.*?\}\}/s', ' ', $value);

            foreach (preg_split('/[\s,]+/', $value, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
                if (! preg_match('/^[a-z!-][a-z0-9:\-\/.\[\]%!]*$/', $class)) {
                    continue;
                }

                $selector = '.'.preg_replace('/([:\/.\[\]%!])/', '\\\\$1', $class);

                if (! str_contains($stylesheets, $selector)) {
                    $missing[$class] = $file->getRelativePathname();
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('adds its styles to the dashboard through atrium', function (): void {
    $styles = app(StyleRegistry::class)->inline();

    expect($styles)->toContain((string) file_get_contents(dirname(__DIR__, 3).'/resources/css/atrium.css'));
});
