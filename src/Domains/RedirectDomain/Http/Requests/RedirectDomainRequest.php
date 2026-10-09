<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Cortex\Http\Request;

abstract class RedirectDomainRequest extends Request
{
    private ?Model $owner = null;

    private bool $ownerResolved = false;

    /**
     * The owner named by `owner_type` and `owner_id`, or null for none.
     */
    protected function owner(): ?Model
    {
        if (! $this->ownerResolved) {
            $type = $this->input('owner_type');
            $id = $this->input('owner_id');

            $this->owner = app(RedirectDomains::class)->owner(
                is_string($type) ? $type : null,
                is_string($id) || is_int($id) ? $id : null,
            );
            $this->ownerResolved = true;
        }

        return $this->owner;
    }

    /**
     * The domain named in the route, by id.
     */
    protected function domain(): RedirectDomainModel
    {
        $id = $this->route('domain');

        abort_unless(is_string($id), 404);

        return RedirectDomainModel::query()->whereKey($id)->firstOrFail();
    }
}
