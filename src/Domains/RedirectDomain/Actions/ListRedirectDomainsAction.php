<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\ListRedirectDomainsData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainsListedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainsListingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;

final class ListRedirectDomainsAction
{
    /**
     * Every stored domain, or one owner's; with `paged` set, one page of
     * them with their owners loaded.
     *
     * @return Collection<int, RedirectDomainModel>|LengthAwarePaginator<int, RedirectDomainModel>
     */
    public function execute(?Model $owner = null, ListRedirectDomainsData $filters = new ListRedirectDomainsData): Collection|LengthAwarePaginator
    {
        RedirectDomainsListingActionEvent::dispatch($owner, $filters);

        $result = $this->perform($owner, $filters);

        RedirectDomainsListedActionEvent::dispatch($result instanceof Collection ? $result : new Collection($result->items()));

        return $result;
    }

    /**
     * @return Collection<int, RedirectDomainModel>|LengthAwarePaginator<int, RedirectDomainModel>
     */
    private function perform(?Model $owner, ListRedirectDomainsData $filters): Collection|LengthAwarePaginator
    {
        $query = RedirectDomainModel::query()
            ->when($filters->source === 'global', fn (Builder $query): Builder => $query->whereNull('owner_type'))
            ->when($filters->source === 'owned', fn (Builder $query): Builder => $query->whereNotNull('owner_type'))
            ->orderBy('domain');

        if ($owner !== null) {
            $query->ownedBy($owner);
        }

        if (! $filters->paged) {
            return $query->get();
        }

        return $query->orderBy('id')
            ->with('owner')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }
}
