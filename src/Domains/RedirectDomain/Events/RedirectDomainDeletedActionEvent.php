<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * A redirect domain was removed; clients already registered on it keep their registration.
 */
final class RedirectDomainDeletedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RedirectDomainModel $domain,
    ) {}
}
