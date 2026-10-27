<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\ListRedirectDomainsData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Cortex\Exceptions\UnexpectedResultException;

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
     * How many stored domains a page shows.
     */
    private const int PER_PAGE = 25;

    /**
     * Every origin a client may register on: the configured ones in their
     * own block, then the stored domains, global or owned, a page at a time,
     * optionally one source's only.
     */
    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', RedirectDomainModel::class);

        $source = $request->query('source');
        $source = is_string($source) && in_array($source, self::SOURCES, true) ? $source : null;

        $stored = $source === 'config' ? null : $this->stored($source, $request);

        /** @var view-string $view */
        $view = 'cortex::ui.redirect-domains.index';

        return view($view, [
            'configured' => $source === null || $source === 'config' ? $this->configured() : [],
            'stored' => $stored,
            'rows' => $stored === null ? [] : $this->rows($stored),
            'source' => $source,
            'sources' => self::SOURCES,
            'enabled' => app(RedirectDomains::class)->enabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', RedirectDomainModel::class, [null]);

        app(CreateRedirectDomainAction::class)->execute(CreateRedirectDomainData::from($request->all()));

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
     * The domains set in `mcp.redirect_domains`, sorted.
     *
     * @return list<string>
     */
    private function configured(): array
    {
        $domains = array_values(array_filter((array) config('mcp.redirect_domains', []), is_string(...)));

        sort($domains);

        return $domains;
    }

    /**
     * One page of stored domains, filtered by source in the query, with the
     * owners of that page only.
     *
     * @return LengthAwarePaginator<int, RedirectDomainModel>
     */
    private function stored(?string $source, Request $request): LengthAwarePaginator
    {
        $page = $request->query('page');

        $stored = app(ListRedirectDomainsAction::class)->execute(null, new ListRedirectDomainsData(
            source: $source,
            paged: true,
            perPage: self::PER_PAGE,
            page: is_numeric($page) ? max(1, (int) $page) : null,
        ));

        return $stored instanceof LengthAwarePaginator ? $stored->withQueryString() : throw UnexpectedResultException::pagedListing();
    }

    /**
     * The table rows for a page of stored domains.
     *
     * @param  LengthAwarePaginator<int, RedirectDomainModel>  $page
     * @return list<array{domain: string, source: string, owner: string|null, type: string|null, model: RedirectDomainModel}>
     */
    private function rows(LengthAwarePaginator $page): array
    {
        return array_values(array_map(fn (RedirectDomainModel $domain): array => [
            'domain' => $domain->domain,
            'source' => $domain->owner_type === null ? 'global' : 'owned',
            'owner' => $domain->owner_type === null ? null : (string) ($domain->owner?->getAttribute('name') ?? $domain->owner_id),
            'type' => $domain->owner_type === null ? null : class_basename($domain->owner_type),
            'model' => $domain,
        ], $page->items()));
    }
}
