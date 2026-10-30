<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ClearServerToolsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\UpdateServerToolsTool;

/*
 * AC 21 (D-040): the docs name the concrete server tool override's routes,
 * MCP tools and ability, carry the deny-by-default snippet, and the
 * CHANGELOG and config keep their stable keywords. The rest of the prose is
 * reviewed with the diff, not grepped.
 */

/**
 * A file of the package, read from the package root.
 */
function packageDocument(string $path): string
{
    $file = dirname(__DIR__, 2).'/'.$path;

    expect(is_file($file))->toBeTrue("{$path} is missing.");

    return (string) file_get_contents($file);
}

/**
 * The CHANGELOG's Unreleased section, up to the next release heading.
 */
function unreleasedChangelog(): string
{
    $changelog = packageDocument('CHANGELOG.md');

    expect(preg_match('/^## \[Unreleased\].*?(?=^## \[)/ms', $changelog, $matches))->toBe(1, 'CHANGELOG.md has no Unreleased section.');

    return $matches[0];
}

/**
 * A Markdown section, from its heading (any level) up to the next heading of
 * the same or a higher level.
 */
function markdownSection(string $markdown, string $heading): string
{
    $found = preg_match('/^(#+) '.preg_quote($heading, '/').'[ \t]*$/m', $markdown, $match, PREG_OFFSET_CAPTURE);

    expect($found)->toBe(1, "No heading \"{$heading}\".");

    $level = strlen($match[1][0]);
    $body = substr($markdown, $match[0][1] + strlen($match[0][0]));
    $end = preg_match('/^#{1,'.$level.'} /m', $body, $next, PREG_OFFSET_CAPTURE) === 1 ? $next[0][1] : strlen($body);

    return $match[0][0].substr($body, 0, $end);
}

/**
 * The fenced PHP blocks of a Markdown document.
 *
 * @return list<string>
 */
function phpBlocks(string $markdown): array
{
    preg_match_all('/^```php\n(.*?)^```/ms', $markdown, $matches);

    return array_values($matches[1]);
}

/**
 * Whether a snippet denies `updateTools` by default: it defines the ability
 * and returns false.
 */
function deniesUpdateToolsByDefault(string $snippet): bool
{
    return str_contains($snippet, 'function updateTools(') && str_contains($snippet, 'return false');
}

/**
 * The comment block of `config/cortex.php` right before a key.
 */
function configBanner(string $key): string
{
    $config = packageDocument('config/cortex.php');
    $position = strpos($config, "'{$key}' =>");

    expect($position)->not->toBeFalse("config/cortex.php has no '{$key}' key.");

    $before = substr($config, 0, (int) $position);
    $start = strrpos($before, '/*');

    expect($start)->not->toBeFalse("config/cortex.php has no banner before '{$key}'.");

    return substr($before, (int) $start);
}

describe('README', function (): void {
    it('names the update and clear routes, as they are registered', function (string $name, string $method): void {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route?->uri())->toBe('cortex/servers/{server}/tools')
            ->and($route?->methods())->toContain($method)
            ->and(packageDocument('README.md'))->toContain("{$method} /cortex/servers/{server}/tools")
            ->toContain($name);
    })->with([
        'update' => ['cortex.servers.tools.update', 'PUT'],
        'clear' => ['cortex.servers.tools.destroy', 'DELETE'],
    ]);

    it('names the update and clear MCP tools by the names they are served under', function (string $tool, string $stem): void {
        $name = app($tool)->name();

        expect($name)->toContain($stem)
            ->and(packageDocument('README.md'))->toContain("`{$name}`");
    })->with([
        'update' => [UpdateServerToolsTool::class, 'update-server-tools'],
        'clear' => [ClearServerToolsTool::class, 'clear-server-tools'],
    ]);

    it('names the updateTools ability and carries a deny-by-default policy snippet', function (): void {
        $readme = packageDocument('README.md');

        expect($readme)->toContain('`updateTools`')
            ->and(array_filter(phpBlocks($readme), deniesUpdateToolsByDefault(...)))->not->toBeEmpty('README.md has no PHP snippet whose updateTools returns false.');
    });

    /*
     * Criterion 18: the redirect domains Cortex puts on other packages'
     * screens, and Atrium as a suggestion.
     */
    it('documents redirect domains on other screens in their own section', function (string $phrase): void {
        expect(markdownSection(packageDocument('README.md'), 'Redirect domains on other screens'))->toContain($phrase);
    })->with([
        'the config key' => ['redirect_domains.screens'],
        'the tab key' => ['cortex.mcp'],
        'the section key' => ['cortex.redirect-domains'],
        'Atrium is optional' => ['Atrium is suggested'],
        'the owner policy' => ["can('update', \$owner)"],
        'replacing the policy' => ['cortex.policies'],
        'the Atrium conflict' => ['conflict'],
        'production skips malformed entries' => ['report'],
        'the authoritative proof' => ['CI'],
    ]);

    it('no longer says Cortex requires Atrium', function (): void {
        expect(packageDocument('README.md'))->not->toContain('which it requires');
    });
});

it('mentions both server tool MCP tools in the Boost skill', function (): void {
    expect(packageDocument('resources/boost/skills/cortex-development/SKILL.md'))
        ->toContain('update-server-tools')
        ->toContain('clear-server-tools');
});

describe('CHANGELOG', function (): void {
    it('keeps the upgrade keywords in the Unreleased section', function (string $keyword): void {
        expect(unreleasedChangelog())->toContain($keyword);
    })->with([
        'optimized autoloader' => ['dump-autoload -o'],
        'publish the migrations' => ['vendor:publish'],
        'run them' => ['migrate'],
        'the ability' => ['updateTools'],
        'restart workers' => ['restart'],
    ]);

    it('opens the Unreleased section with the updateTools upgrade note', function (): void {
        expect(preg_match('/^### .*?(?=^### )/ms', unreleasedChangelog(), $first))->toBe(1)
            ->and($first[0])->toContain('updateTools')
            ->toContain('/cortex/servers/{server}/tools');
    });

    /*
     * Criterion 18: the upgrade note for Atrium screens and owned redirect
     * domains names each of its seven topics, and the Atrium conflict.
     */
    it('carries the Atrium screens and owned redirect domains upgrade note', function (string $keyword): void {
        expect(markdownSection(unreleasedChangelog(), 'Upgrade notes (Atrium screens and owned redirect domains)'))->toContain($keyword);
    })->with([
        'Atrium suggested' => ['suggest'],
        'the new config key' => ['redirect_domains'],
        'the owner fields' => ['owner_type'],
        'owned domains ask update on the owner' => ['update'],
        'history UI removed' => ['History'],
        'the Roster tab key' => ['?tab=mcp'],
        'grouped merge' => ['merge'],
        'the Atrium conflict' => ['conflict'],
    ]);
});

describe('config/cortex.php', function (): void {
    it('documents the warning TTL in its own banner', function (): void {
        $config = require dirname(__DIR__, 2).'/config/cortex.php';
        $banner = configBanner('warning_ttl');

        expect($config['mcp']['warning_ttl'])->toBe(3600)
            ->and($banner)->toContain('MCP Warning Rate Limit')
            ->toContain('warning_ttl')
            ->toContain('3600');
    });

    it('names updateTools in the policies comment, with a deny-by-default snippet', function (): void {
        $banner = configBanner('policies');

        expect($banner)->toContain('updateTools')
            ->toContain('guests')
            ->and(deniesUpdateToolsByDefault($banner))->toBeTrue();
    });
});

it('lists the ConcreteServer domain in the domains standard', function (): void {
    expect(packageDocument('docs/agent/standards/cortex/domains.md'))->toContain('`ConcreteServer`');
});
