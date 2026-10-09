<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A redirect domain was added; MCP clients may now register redirect URIs on it.
 */
final class RedirectDomainCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RedirectDomainModel $domain,
    ) {}
}
