<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The RedirectDomainModel `replicating` Eloquent event.
 */
final class RedirectDomainReplicatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RedirectDomainModel $domain) {}

    public function model(): Model
    {
        return $this->domain;
    }

    public function hook(): string
    {
        return 'replicating';
    }
}
