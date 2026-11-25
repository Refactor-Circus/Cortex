<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The summary of Cortex's settings was shown: the configured providers, the
 * registered tool, agent and server counts and whether the cache is on.
 */
final class SettingsSummaryShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array{providers: list<array{name: string, models: list<string>}>, tool_count: int, agent_count: int, server_count: int, cache_enabled: bool}  $summary
     */
    public function __construct(
        public array $summary,
    ) {}
}
