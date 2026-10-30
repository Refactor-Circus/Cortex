<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\ListRedirectDomainsData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Cortex\Exceptions\UnexpectedResultException;

/**
 * Every stored redirect domain, whoever owns it. Domains added on this
 * screen belong to no owner; a host screen's form names its record as the
 * owner with `owner_type` and `owner_id`.
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
    public function index(Request $request, ScreenExtensions $extensions): View
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
            // Only stored rows get `row.actions`: a configured domain is no model.
            'resolved' => $extensions->resolve(Screens::declare('cortex.redirect-domains', Screen::LIST, null, RedirectDomainModel::class), $request),
        ]);
    }

    /**
     * Add a domain, for the owner named by `owner_type` and `owner_id` when
     * the form names one (a host screen's record), else for no owner.
     */
    public function store(Request $request): RedirectResponse
    {
        $owner = $this->owner($request);

        $this->authorizeScreen('create', RedirectDomainModel::class, [$owner]);

        app(CreateRedirectDomainAction::class)->execute(CreateRedirectDomainData::from($request->all()), $owner);

        return $this->back($request, $owner)->with('status', __('cortex::cortex.redirect_domain_added'));
    }

    /**
     * Remove a domain. With an owner named, only one of that owner's domains
     * is found, so a form for one owner cannot remove another's.
     */
    public function destroy(Request $request, string $domain): RedirectResponse
    {
        $owner = $this->owner($request);

        $model = $owner === null
            ? RedirectDomainModel::query()->findOrFail($domain)
            : RedirectDomainModel::query()->ownedBy($owner)->whereKey($domain)->firstOrFail();

        $this->authorizeScreen('delete', $model);

        app(DeleteRedirectDomainAction::class)->execute($model);

        return $this->back($request, $owner)->with('status', __('cortex::cortex.redirect_domain_removed'));
    }

    /**
     * The owner the form names, or null when it names none. Both fields or
     * neither: one without the other is invalid. The type is a morph alias or
     * a model class `RedirectDomains::owner()` resolves; anything else, and an
     * unknown key, is not found.
     */
    private function owner(Request $request): ?Model
    {
        $validated = Validator::make($request->only(['owner_type', 'owner_id']), [
            'owner_type' => ['nullable', 'required_with:owner_id', 'string', 'max:255'],
            'owner_id' => ['nullable', 'required_with:owner_type', 'string', 'max:255'],
        ])->validate();

        $type = $validated['owner_type'] ?? null;
        $id = $validated['owner_id'] ?? null;

        return app(RedirectDomains::class)->owner(
            is_string($type) ? $type : null,
            is_string($id) ? $id : null,
        );
    }

    /**
     * Where a write goes next: without an owner, the redirect-domains screen;
     * with one, back to the page the form was on when its scheme, host and
     * port are exactly this request's, else (a foreign or missing referrer)
     * the redirect-domains screen. Only the path and query are kept, on the
     * request's own origin, so no referrer can send the browser elsewhere.
     */
    private function back(Request $request, ?Model $owner): RedirectResponse
    {
        $index = route('atrium.cortex.redirect-domains.index');

        if ($owner === null) {
            return redirect()->to($index);
        }

        $origin = $request->getSchemeAndHttpHost();
        $previous = parse_url(url()->previous($index));
        $own = parse_url($origin);

        if (! is_array($previous) || ! is_array($own)) {
            return redirect()->to($index);
        }

        foreach (['scheme', 'host', 'port'] as $part) {
            if (($previous[$part] ?? null) !== ($own[$part] ?? null)) {
                return redirect()->to($index);
            }
        }

        $query = isset($previous['query']) ? '?'.$previous['query'] : '';

        return redirect()->to($origin.($previous['path'] ?? '/').$query);
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
