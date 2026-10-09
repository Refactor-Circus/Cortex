<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Cortex\Mcp\Request;

abstract class RedirectDomainMcpRequest extends Request
{
    private ?Model $owner = null;

    private bool $ownerResolved = false;

    /**
     * The owner named by `owner_type` and `owner_id`, or null for none.
     * Unknown owners surface as the base request's not-found error.
     */
    protected function owner(): ?Model
    {
        if (! $this->ownerResolved) {
            $type = $this->get('owner_type');
            $id = $this->get('owner_id');

            $this->owner = app(RedirectDomains::class)->owner(
                is_string($type) ? $type : null,
                is_string($id) || is_int($id) ? $id : null,
            );
            $this->ownerResolved = true;
        }

        return $this->owner;
    }

    protected function domain(): RedirectDomainModel
    {
        return RedirectDomainModel::query()->whereKey((string) $this->get('id'))->firstOrFail();
    }
}
