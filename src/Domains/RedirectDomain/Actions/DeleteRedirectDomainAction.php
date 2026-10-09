<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Actions;

use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainDeletedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainDeletingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;

final class DeleteRedirectDomainAction
{
    public function __construct(private readonly RedirectDomains $domains) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(RedirectDomainModel $domain): void
    {
        RedirectDomainDeletingActionEvent::dispatch($domain);

        $this->perform($domain);

        RedirectDomainDeletedActionEvent::dispatch($domain);
    }

    private function perform(RedirectDomainModel $domain): void
    {
        $domain->delete();

        $this->domains->forget();
    }
}
