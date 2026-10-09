<x-atrium::layout :title="__('cortex::cortex.redirect_domains')">
    <x-atrium::page-header :title="__('cortex::cortex.redirect_domains')" :description="__('cortex::cortex.redirect_domains_description')" />

    <div class="mt-5 flex flex-col gap-4">
        <x-atrium::flash :keys="['domain']" />

        @unless ($enabled)
            <x-atrium::alert variant="warning">{{ __('cortex::cortex.redirect_domains_disabled') }}</x-atrium::alert>
        @endunless

        @cortexCan('create', \JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel::class, [null])
            <x-atrium::card :title="__('cortex::cortex.add_domain')">
                <form method="POST" action="{{ route('atrium.cortex.redirect-domains.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <x-atrium::form.input name="domain" :label="__('cortex::cortex.domain')" :hint="__('cortex::cortex.domain_hint')" placeholder="https://claude.ai" wrapper="w-80" required />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('cortex::cortex.add_domain')" type="submit" variant="primary" data-testid="add-redirect-domain" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
        @endcortexCan

        <div class="flex flex-wrap gap-2" data-testid="redirect-domain-sources">
            <x-atrium::chip :href="route('atrium.cortex.redirect-domains.index')" :active="$source === null" data-testid="source-all"
                            :aria-current="$source === null ? 'true' : null">{{ __('cortex::cortex.all_sources') }}</x-atrium::chip>
            @foreach ($sources as $option)
                <x-atrium::chip :href="route('atrium.cortex.redirect-domains.index', ['source' => $option])" :active="$source === $option" data-testid="source-{{ $option }}"
                                :aria-current="$source === $option ? 'true' : null">{{ __('cortex::cortex.source_'.$option) }}</x-atrium::chip>
            @endforeach
        </div>

        @if ($rows === [])
            <x-atrium::empty-state :title="__('cortex::cortex.no_redirect_domains')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.domain') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.source') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.created') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading class="text-right">{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($rows as $row)
                    <x-atrium::table.row data-testid="redirect-domain-{{ $row['source'] }}">
                        <x-atrium::table.cell><code class="text-xs">{{ $row['domain'] }}</code></x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($row['source'] === 'owned')
                                {{ $row['owner'] }}
                                <span class="text-xs opacity-60">{{ $row['type'] }}</span>
                            @else
                                <x-atrium::badge>{{ __('cortex::cortex.source_'.$row['source']) }}</x-atrium::badge>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $row['model']?->created_at?->diffForHumans() }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($row['model'] !== null)
                                @cortexCan('delete', $row['model'])
                                    <form method="POST" action="{{ route('atrium.cortex.redirect-domains.destroy', $row['model']->id) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="trash" :label="__('cortex::cortex.remove_domain')" type="submit" size="sm" variant="ghost"
                                                               data-testid="remove-redirect-domain-{{ $row['model']->id }}" />
                                    </form>
                                @endcortexCan
                            @else
                                <span class="block text-right text-xs opacity-60">{{ __('cortex::cortex.configured_redirect_domains_hint') }}</span>
                            @endif
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
