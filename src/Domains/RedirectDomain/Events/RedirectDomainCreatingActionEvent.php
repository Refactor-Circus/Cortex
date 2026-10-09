<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * A redirect domain is about to be added.
 */
final class RedirectDomainCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public ?Model $owner = null,
    ) {}
}
