<x-atrium::layout :title="__('cortex::cortex.tools')">
    <x-atrium::page-header :title="__('cortex::cortex.tools')" />

    <div class="mt-5 flex flex-col gap-4" x-data="{ search: '' }">
        @if ($tags !== [])
            {{-- The tag filter is a query string, so a filtered list can be linked to.
                 Tags are named, so they are linked badges, like the tags in the table. --}}
            <div class="flex flex-wrap items-center gap-2" data-testid="tool-tags">
                <a href="{{ route('atrium.cortex.tools.index') }}" data-testid="tag-all" @if ($tag === null) aria-current="true" @endif>
                    <x-atrium::badge :variant="$tag === null ? 'primary' : 'neutral'">{{ __('cortex::cortex.all_tags') }}</x-atrium::badge>
                </a>
                @foreach ($tags as $option)
                    <a href="{{ route('atrium.cortex.tools.index', ['tag' => $option]) }}" data-testid="tag-{{ $option }}" @if ($tag === $option) aria-current="true" @endif>
                        <x-atrium::badge :variant="$tag === $option ? 'primary' : 'neutral'">{{ $option }}</x-atrium::badge>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($tools === [])
            <x-atrium::empty-state :title="$tag === null ? __('cortex::cortex.no_tools') : __('cortex::cortex.no_tools_tagged', ['tag' => $tag])" />
        @else
            <input type="search" x-model="search" placeholder="{{ __('cortex::cortex.search_tools') }}"
                   class="w-full max-w-sm rounded-radius border border-outline bg-surface-alt px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark-alt/50" />

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
                            @cortexCan('view', \JayI\Cortex\Atrium\ScreenAccess::toolDescription($tool['name'], $descriptions->get($tool['name'])))
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
