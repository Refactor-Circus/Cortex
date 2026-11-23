<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ClearServerToolsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\UpdateServerToolsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListRunnableAgentsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\SearchAgentsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowSettingsSummaryTool;

/*
 * AC 21 (D-040): the docs name the concrete server tool override's routes,
 * MCP tools and ability, carry the deny-by-default snippet, and the
 * CHANGELOG and config keep their stable keywords.
 *
 * Cortex v2 (AC 17-19): the CHANGELOG's v2.0.0 notes name what left with the
 * Atrium UI and what replaced it; README, the Boost skill, CLAUDE.md and
 * AGENTS.md describe the JSON API + MCP package with Atrium v2 as its
 * optional UI; the local standards no longer mention Atrium. The rest of the
 * prose is reviewed with the diff, not grepped.
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
 * A release section of the CHANGELOG, from its `## [vX.Y.Z]` heading up to
 * the next release heading.
 */
function changelogRelease(string $version): string
{
    $changelog = packageDocument('CHANGELOG.md');
    $pattern = '/^## \['.preg_quote($version, '/').'\].*?(?=^## \[|\z)/ms';

    expect(preg_match($pattern, $changelog, $matches))->toBe(1, "CHANGELOG.md has no {$version} section.");

    return $matches[0];
}

/**
 * The CHANGELOG's v0.1.0 section, which carries the server tool override's
 * upgrade notes.
 */
function releaseChangelog(): string
{
    return changelogRelease('v0.1.0');
}

/**
 * The documents that describe Cortex to people and agents. CLAUDE.md is a
 * link to AGENTS.md; both are read, as tools read either.
 *
 * @return array<string, array{string}>
 */
function describingDocuments(): array
{
    return [
        'README' => ['README.md'],
        'Boost skill' => ['resources/boost/skills/cortex-development/SKILL.md'],
        'CLAUDE.md' => ['CLAUDE.md'],
        'AGENTS.md' => ['AGENTS.md'],
    ];
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
});

it('mentions both server tool MCP tools in the Boost skill', function (): void {
    expect(packageDocument('resources/boost/skills/cortex-development/SKILL.md'))
        ->toContain('update-server-tools')
        ->toContain('clear-server-tools');
});

describe('CHANGELOG', function (): void {
    it('keeps the upgrade keywords in the v0.1.0 section', function (string $keyword): void {
        expect(releaseChangelog())->toContain($keyword);
    })->with([
        'optimized autoloader' => ['dump-autoload -o'],
        'publish the migrations' => ['vendor:publish'],
        'run them' => ['migrate'],
        'the ability' => ['updateTools'],
        'restart workers' => ['restart'],
    ]);

    it('opens the v0.1.0 section with the updateTools upgrade note', function (): void {
        expect(preg_match('/^### .*?(?=^### )/ms', releaseChangelog(), $first))->toBe(1)
            ->and($first[0])->toContain('updateTools')
            ->toContain('/cortex/servers/{server}/tools');
    });
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

describe('CHANGELOG v2.0.0', function (): void {
    it('names what left with the Atrium UI and what replaces it', function (string $keyword): void {
        expect(changelogRelease('v2.0.0'))->toContain($keyword);
    })->with([
        'the UI moved to Atrium v2' => ['Atrium v2'],
        'Atrium v2 integration config' => ['atrium.integrations.cortex.'],
        'removed ui keys' => ['cortex.ui.'],
        'removed atrium keys' => ['cortex.atrium.'],
        'removed feature class' => ['CortexSupportFeature'],
        'removed views' => ['cortex::ui.'],
        'removed publish tag' => ['cortex-views'],
        'removed routes' => ['atrium.cortex.'],
        'search endpoint' => ['cortex.search.index'],
        'runnable agents endpoint' => ['cortex.runnable-agents.index'],
        'settings summary endpoint' => ['cortex.settings-summary.show'],
        'search tool' => ['search-agents-tool'],
        'runnable agents tool' => ['list-runnable-agents-tool'],
        'settings summary tool' => ['show-settings-summary-tool'],
        'OpenAPI document' => ['openapi.json'],
        'client folder' => ['client/'],
        'client package' => ['@refactor-circus/cortex-client'],
        'client script' => ['client:generate'],
    ]);

    it('comes before v0.1.0 and has upgrade notes', function (): void {
        $changelog = packageDocument('CHANGELOG.md');

        expect(strpos($changelog, '## [v2.0.0]'))->toBeLessThan((int) strpos($changelog, '## [v0.1.0]'))
            ->and(changelogRelease('v2.0.0'))->toMatch('/^### Upgrade notes/m')
            ->toContain('v0.1.0...v2.0.0');
    });
});

describe('README, Boost skill, CLAUDE.md and AGENTS.md', function (): void {
    it('describe the JSON API and MCP package with its OpenAPI document and client', function (string $document, string $keyword): void {
        expect(packageDocument($document))->toContain($keyword);
    })->with(describingDocuments())->with([
        'search endpoint' => ['cortex.search.index'],
        'runnable agents endpoint' => ['cortex.runnable-agents.index'],
        'settings summary endpoint' => ['cortex.settings-summary.show'],
        'OpenAPI document' => ['openapi.json'],
        'client package' => ['@refactor-circus/cortex-client'],
        'client script' => ['client:generate'],
        'optional UI' => ['Atrium v2'],
    ]);

    it('have no dashboard section and no removed config key', function (string $document): void {
        $text = packageDocument($document);

        expect($text)->not->toContain('cortex.ui')
            ->not->toContain('cortex.atrium')
            ->not->toContain('cortexCan')
            ->not->toContain('CortexSupportFeature')
            ->not->toContain('without-atrium')
            ->not->toMatch('/^#{1,6} Dashboard\b/m');
    })->with(describingDocuments());

    it('name the new MCP tools by the names they are served under', function (string $document, string $tool): void {
        expect(packageDocument($document))->toContain('`'.app($tool)->name().'`');
    })->with([
        'README' => ['README.md'],
        'Boost skill' => ['resources/boost/skills/cortex-development/SKILL.md'],
    ])->with([
        'search' => [SearchAgentsTool::class],
        'runnable agents' => [ListRunnableAgentsTool::class],
        'settings summary' => [ShowSettingsSummaryTool::class],
    ]);

    it('name the new endpoints by their registered paths', function (string $name, string $path): void {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route?->uri())->toBe($path)
            ->and(packageDocument('README.md'))->toContain("/{$path}");
    })->with([
        'search' => ['cortex.search.index', 'cortex/search'],
        'runnable agents' => ['cortex.runnable-agents.index', 'cortex/runnable-agents'],
        'settings summary' => ['cortex.settings-summary.show', 'cortex/settings-summary'],
    ]);
});

it('names the OpenAPI document and the client in the routes banner', function (): void {
    expect(configBanner('routes'))->toContain('openapi.json')
        ->toContain('client/')
        ->toContain('composer client:generate');
});

describe('local standards', function (): void {
    it('has no Atrium screens standard', function (): void {
        expect(is_file(dirname(__DIR__, 2).'/docs/agent/standards/cortex/atrium-screens.md'))->toBeFalse();
    });

    it('no longer mentions Atrium', function (string $standard): void {
        expect(packageDocument("docs/agent/standards/cortex/{$standard}.md"))->not->toMatch('/atrium/i');
    })->with(['domains', 'optional-surfaces', 'testbench-environment']);

    it('lists the ConcreteServer domain in the domains standard', function (): void {
        expect(packageDocument('docs/agent/standards/cortex/domains.md'))->toContain('`ConcreteServer`');
    });

    it('names the package-wide reads the VirtualAgent domain serves', function (string $keyword): void {
        expect(packageDocument('docs/agent/standards/cortex/domains.md'))->toContain($keyword);
    })->with(['search', 'runnable agents', 'settings summary']);

    it('pins the providers the TestCase boots, in order', function (): void {
        expect(packageDocument('docs/agent/standards/cortex/testbench-environment.md'))
            ->toContain('`AiServiceProvider`, `McpServiceProvider`, then `CortexServiceProvider`');
    });
});
