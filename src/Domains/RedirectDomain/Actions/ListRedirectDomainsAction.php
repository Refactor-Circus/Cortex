<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JayI\Cortex\Domains\RedirectDomain\Events\RedirectDomainsListedActionEvent;
use JayI\Cortex\Domains\RedirectDomain\Events\RedirectDomainsListingActionEvent;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;

final class ListRedirectDomainsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'owner_type' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'required_with:owner_type', 'string', 'max:255'],
        ];
    }

    /**
     * Every stored domain, or one owner's.
     *
     * @return Collection<int, RedirectDomainModel>
     */
    public function execute(?Model $owner = null): Collection
    {
        RedirectDomainsListingActionEvent::dispatch($owner);

        $result = $this->perform($owner);

        RedirectDomainsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return Collection<int, RedirectDomainModel>
     */
    private function perform(?Model $owner): Collection
    {
        $query = RedirectDomainModel::query()->orderBy('domain');

        if ($owner !== null) {
            $query->ownedBy($owner);
        }

        return $query->get();
    }
}
