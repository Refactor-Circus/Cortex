<x-atrium::layout :title="__('cortex::cortex.tools')">
    <x-atrium::page-header :title="__('cortex::cortex.tools')" />

    <div class="mt-5 flex flex-col gap-4" x-data="{ search: '' }">
        @if ($tags !== [])
            {{-- The tag filter is a query string, so a filtered list can be linked to:
                 each tag is a linked chip, pressed when it is the current filter. --}}
            <div class="flex flex-wrap items-center gap-2" data-testid="tool-tags">
                <x-atrium::chip :href="route('atrium.cortex.tools.index')" :active="$tag === null" data-testid="tag-all"
                                :aria-current="$tag === null ? 'true' : null">{{ __('cortex::cortex.all_tags') }}</x-atrium::chip>
                @foreach ($tags as $option)
                    <x-atrium::chip :href="route('atrium.cortex.tools.index', ['tag' => $option])" :active="$tag === $option" data-testid="tag-{{ $option }}"
                                    :aria-current="$tag === $option ? 'true' : null">{{ $option }}</x-atrium::chip>
                @endforeach
            </div>
        @endif

        @if ($tools === [])
            <x-atrium::empty-state :title="$tag === null ? __('cortex::cortex.no_tools') : __('cortex::cortex.no_tools_tagged', ['tag' => $tag])" />
        @else
            <x-atrium::search-input x-model="search" :placeholder="__('cortex::cortex.search_tools')" data-testid="tool-search" />

            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.tags') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($tools as $tool)
                    <x-atrium::table.row x-show="search === '' || {{ Js::from(strtolower($tool['name'])) }}.includes(search.toLowerCase())">
                        <x-atrium::table.cell><code class="text-xs">{{ $tool['name'] }}</code></x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($tool['tags'] as $toolTag)
                                    <a href="{{ route('atrium.cortex.tools.index', ['tag' => $toolTag]) }}">
                                        <x-atrium::badge :variant="$tag === $toolTag ? 'primary' : 'neutral'">{{ $toolTag }}</x-atrium::badge>
                                    </a>
                                @endforeach
                            </div>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @cortexCan('view', \RefactorCircus\Cortex\Atrium\ScreenAccess::toolDescription($tool['name'], $descriptions->get($tool['name'])))
                                <x-atrium::icon-button icon="document-text" :label="__('cortex::cortex.description')" size="sm" variant="outline"
                                                       :href="route('atrium.cortex.tools.description', $tool['name'])"
                                                       data-testid="description-{{ $tool['name'] }}" />
                            @endcortexCan
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
