<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainDeletedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainDeletingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Keystone\Actions\Action;

final class DeleteRedirectDomainAction extends Action
{
    public function __construct(private readonly RedirectDomains $domains) {}

    protected function handle(RedirectDomainModel $domain): void
    {
        RedirectDomainDeletingActionEvent::dispatch($domain);

        $this->perform($domain);

        RedirectDomainDeletedActionEvent::dispatch($domain);
    }

    private function perform(RedirectDomainModel $domain): void
    {
        DB::transaction(function () use ($domain): void {
            $domain->delete();

            DB::afterCommit(fn () => $this->domains->forget());
        });
    }
}
