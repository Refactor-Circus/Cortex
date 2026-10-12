<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainCreatedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainCreatingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;

final class CreateRedirectDomainAction
{
    public function __construct(private readonly RedirectDomains $domains) {}

    /**
     * Add a domain for an owner, or for every client when there is none.
     * Adding one the owner already has returns it.
     */
    public function execute(CreateRedirectDomainData $data, ?Model $owner = null): RedirectDomainModel
    {
        RedirectDomainCreatingActionEvent::dispatch($data, $owner);

        $result = $this->perform($data, $owner);

        RedirectDomainCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateRedirectDomainData $data, ?Model $owner): RedirectDomainModel
    {
        $domain = RedirectDomains::normalize($data->domain)
            ?? throw ValidationException::withMessages(['domain' => __('cortex::cortex.redirect_domain_invalid')]);

        return DB::transaction(function () use ($domain, $owner): RedirectDomainModel {
            $existing = RedirectDomainModel::query()->ownedBy($owner)->where('domain', $domain)->first();

            if ($existing !== null) {
                return $existing;
            }

            $model = new RedirectDomainModel(['domain' => $domain]);

            if ($owner !== null) {
                $model->owner()->associate($owner);
            }

            $model->save();

            DB::afterCommit(fn () => $this->domains->forget());

            return $model;
        });
    }
}
