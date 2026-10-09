<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * Redirect domains were listed.
 */
final class RedirectDomainsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, RedirectDomainModel>  $domains
     */
    public function __construct(
        public Collection $domains,
    ) {}
}
