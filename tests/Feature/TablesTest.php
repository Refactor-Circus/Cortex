<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Support\Tables;

it('ships the default table names in its config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/cortex.php';

    expect($config['tables'])->toBe(Tables::DEFAULTS);
});

it('keeps the default for tables a published config leaves out', function (): void {
    config()->set('cortex.tables', ['virtual_agents' => 'crm_virtual_agents']);

    expect(Tables::name('virtual_agents'))->toBe('crm_virtual_agents')
        ->and(Tables::name('virtual_agent_versions'))->toBe('cortex_virtual_agent_versions')
        ->and((new VirtualAgentModel)->getTable())->toBe('crm_virtual_agents')
        ->and((new VirtualAgentVersionModel)->getTable())->toBe('cortex_virtual_agent_versions');
});
