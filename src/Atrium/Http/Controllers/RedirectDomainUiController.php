<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;

/**
 * Every stored redirect domain, whoever owns it. Domains added here belong
 * to no owner.
 */
final class RedirectDomainUiController
{
    use AuthorizesScreens;

    /**
     * Where an allowed origin comes from: `mcp.redirect_domains`, a stored
     * domain with no owner, or one an organization or user owns.
     *
     * @var list<string>
     */
    private const array SOURCES = ['config', 'global', 'owned'];

    /**
     * Every origin a client may register on, compiled from config and every
     * stored domain, global or owned, optionally one source's only.
     */
    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', RedirectDomainModel::class);

        $source = $request->query('source');
        $source = is_string($source) && in_array($source, self::SOURCES, true) ? $source : null;

        /** @var view-string $view */
        $view = 'cortex::ui.redirect-domains.index';

        return view($view, [
            'rows' => array_values(array_filter(
                $this->rows(),
                fn (array $row): bool => $source === null || $row['source'] === $source,
            )),
            'source' => $source,
            'sources' => self::SOURCES,
            'enabled' => app(RedirectDomains::class)->enabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', RedirectDomainModel::class, [null]);

        app(CreateRedirectDomainAction::class)->execute($request->validate(CreateRedirectDomainAction::rules()));

        return redirect()
            ->route('atrium.cortex.redirect-domains.index')
            ->with('status', __('cortex::cortex.redirect_domain_added'));
    }

    public function destroy(string $domain): RedirectResponse
    {
        $model = RedirectDomainModel::query()->findOrFail($domain);

        $this->authorizeScreen('delete', $model);

        app(DeleteRedirectDomainAction::class)->execute($model);

        return redirect()
            ->route('atrium.cortex.redirect-domains.index')
            ->with('status', __('cortex::cortex.redirect_domain_removed'));
    }

    /**
     * @return list<array{domain: string, source: string, owner: string|null, type: string|null, model: RedirectDomainModel|null}>
     */
    private function rows(): array
    {
        $configured = array_map(fn (string $domain): array => [
            'domain' => $domain,
            'source' => 'config',
            'owner' => null,
            'type' => null,
            'model' => null,
        ], array_values(array_filter((array) config('mcp.redirect_domains', []), is_string(...))));

        $stored = app(ListRedirectDomainsAction::class)->execute()->load('owner')->map(fn (RedirectDomainModel $domain): array => [
            'domain' => $domain->domain,
            'source' => $domain->owner_type === null ? 'global' : 'owned',
            'owner' => $domain->owner_type === null ? null : (string) ($domain->owner?->getAttribute('name') ?? $domain->owner_id),
            'type' => $domain->owner_type === null ? null : class_basename($domain->owner_type),
            'model' => $domain,
        ])->all();

        $rows = [...$configured, ...$stored];

        usort($rows, fn (array $a, array $b): int => [$a['domain'], array_search($a['source'], self::SOURCES, true)]
            <=> [$b['domain'], array_search($b['source'], self::SOURCES, true)]);

        return $rows;
    }
}
