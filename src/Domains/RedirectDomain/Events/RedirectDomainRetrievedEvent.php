<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The RedirectDomainModel `retrieved` Eloquent event.
 */
final class RedirectDomainRetrievedEvent implements ModelLifecycleEvent
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
        return 'retrieved';
    }
}
