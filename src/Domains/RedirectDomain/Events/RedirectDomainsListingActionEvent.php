<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * Redirect domains are about to be listed.
 */
final class RedirectDomainsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ?Model $owner = null,
    ) {}
}
