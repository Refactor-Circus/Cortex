{{--
    An owner's redirect domains on another package's screen: the `cortex.mcp`
    tab's panel and the `cortex.redirect-domains` section both render this
    view (ScreenRegistrations). `$record` is the owner; `$domains`,
    `$ownerType` and `$ownerId` come from the resolver. The forms post the
    owner, and the controller sends the browser back to this page.
--}}
@isset($ownerType)
    <x-atrium::card :title="__('cortex::cortex.redirect_domains')" :subtitle="__('cortex::cortex.owned_redirect_domains_hint')">
        <div class="flex flex-col gap-4">
            @cortexCan('create', \RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel::class, [$record])
                <form method="POST" action="{{ route('atrium.cortex.redirect-domains.store') }}" class="flex flex-wrap items-end gap-3" data-testid="add-redirect-domain">
                    @csrf
                    <input type="hidden" name="owner_type" value="{{ $ownerType }}">
                    <input type="hidden" name="owner_id" value="{{ $ownerId }}">
                    <x-atrium::form.input id="cortex-owned-domain" name="domain" :label="__('cortex::cortex.domain')" :hint="__('cortex::cortex.domain_hint')" placeholder="https://claude.ai" wrapper="w-80" required />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('cortex::cortex.add_owned_domain')" type="submit" variant="primary" />
                    </x-atrium::form.actions>
                </form>
            @endcortexCan

            @if ($domains->isEmpty())
                <x-atrium::empty-state :title="__('cortex::cortex.no_redirect_domains')" />
            @else
                <x-atrium::table striped data-testid="owned-redirect-domains">
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('cortex::cortex.domain') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('cortex::cortex.created') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading class="text-right">{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($domains as $domain)
                        <x-atrium::table.row data-testid="owned-redirect-domain">
                            <x-atrium::table.cell><code class="text-xs">{{ $domain->domain }}</code></x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $domain->created_at?->diffForHumans() }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @cortexCan('delete', $domain)
                                    <form method="POST" action="{{ route('atrium.cortex.redirect-domains.destroy', $domain->id) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="owner_type" value="{{ $ownerType }}">
                                        <input type="hidden" name="owner_id" value="{{ $ownerId }}">
                                        <x-atrium::icon-button icon="trash" :label="__('cortex::cortex.remove_domain')" type="submit" size="sm" variant="ghost"
                                                               data-testid="remove-redirect-domain" />
                                    </form>
                                @endcortexCan
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
            @endif
        </div>
    </x-atrium::card>
@endisset
