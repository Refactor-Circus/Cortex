<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Cortex\Atrium\CortexPlugin;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

/**
 * Cortex has no history UI of its own: Keen attaches History to every
 * screen by wildcard. The JSON history route and MCP tool stay (HistoryTest).
 */
it('offers no history navigation item', function (): void {
    $routes = array_map(fn (NavItem $item): ?string => $item->route, app(CortexPlugin::class)->navigation());

    expect($routes)->not->toContain('atrium.history.show');
});

it('renders no history on the screens that used to show it', function (): void {
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
    config()->set('cortex.tools', ['echo' => EchoTool::class]);

    VirtualAgentModel::factory()->published()->create(['slug' => 'helper']);
    ToolDescriptionModel::query()->create(['tool' => 'echo']);

    foreach ([
        route('atrium.cortex.virtual-agents.index'),
        route('atrium.cortex.virtual-agents.edit', 'helper'),
        route('atrium.cortex.tools.description', 'echo'),
    ] as $url) {
        $this->get($url)->assertOk()->assertDontSee('audit-trail', false);
    }
});

it('names no history component or history navigation item in its code and docs', function (): void {
    $root = str_replace('\\', '/', dirname(__DIR__, 2));
    $files = [$root.'/README.md', $root.'/CLAUDE.md'];

    foreach (['resources', 'src', 'docs/agent/standards'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                // Forward slashes on Windows too, so the expectation below matches.
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
    }

    $offending = array_values(array_filter($files, function (string $path): bool {
        $contents = (string) file_get_contents($path);

        return str_contains($contents, 'audit-trail') || str_contains($contents, 'historyNavItem');
    }));

    // resources/boost is under resources.
    expect($files)->toContain($root.'/resources/boost/skills/cortex-development/SKILL.md')
        ->and($offending)->toBe([]);
});
