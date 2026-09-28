<x-atrium::layout :title="__('cortex::cortex.concrete_agents')">
    <x-atrium::page-header :title="__('cortex::cortex.concrete_agents')" :description="__('cortex::cortex.concrete_agents_description')" />

    <div class="mt-5">
        @include('cortex::ui.partials.status')

        @if ($agents === [])
            <x-atrium::empty-state :title="__('cortex::cortex.no_concrete_agents')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.prompt') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.tools') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($agents as $agent)
                    @php($published = $agent['override']?->publishedVersion?->version)

                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="font-medium underline-offset-2 hover:underline"
                               href="{{ route('atrium.cortex.concrete-agents.show', $agent['name']) }}"><code class="text-xs">{{ $agent['name'] }}</code></a>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($published !== null)
                                <x-atrium::badge variant="success">{{ __('cortex::cortex.override', ['version' => $published]) }}</x-atrium::badge>
                            @else
                                <x-atrium::badge>{{ __('cortex::cortex.from_code') }}</x-atrium::badge>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if (! $agent['tools_overridable'])
                                <x-atrium::badge>{{ __('cortex::cortex.locked') }}</x-atrium::badge>
                            @elseif ($agent['override']?->tools !== null)
                                <x-atrium::badge variant="success">{{ __('cortex::cortex.overridden') }}</x-atrium::badge>
                            @else
                                <x-atrium::badge>{{ __('cortex::cortex.from_code') }}</x-atrium::badge>
                            @endif
                            <span class="ml-1 text-xs opacity-75">{{ count($agent['tools']) }}</span>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex items-center gap-2">
                                <x-atrium::button size="sm" variant="outline"
                                                  :href="route('atrium.cortex.concrete-agents.show', $agent['name'])">
                                    {{ __('cortex::cortex.manage') }}
                                </x-atrium::button>
                                <x-atrium::button size="sm" variant="outline"
                                                  :href="route('atrium.cortex.run', ['agent' => 'concrete:'.$agent['name']])">
                                    {{ __('cortex::cortex.run') }}
                                </x-atrium::button>
                            </div>
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
