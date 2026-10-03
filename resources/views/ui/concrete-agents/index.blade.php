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
                    @php($subject = \JayI\Cortex\Atrium\ScreenAccess::concreteAgent($agent['name'], $agent['override']))

                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            @cortexCan('view', $subject)
                                <a class="font-medium underline-offset-2 hover:underline"
                                   href="{{ route('atrium.cortex.concrete-agents.show', $agent['name']) }}"><code class="text-xs">{{ $agent['name'] }}</code></a>
                            @else
                                <code class="text-xs">{{ $agent['name'] }}</code>
                            @endcortexCan
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex items-center gap-2">
                                @if ($published !== null)
                                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('overridden')" :label="__('cortex::cortex.override', ['version' => $published])" data-status="overridden" />
                                    <span class="text-xs opacity-75">v{{ $published }}</span>
                                @else
                                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('from_code')" :label="__('cortex::cortex.from_code')" data-status="from_code" />
                                @endif
                            </div>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex items-center gap-2">
                                @if (! $agent['tools_overridable'])
                                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('locked')" :label="__('cortex::cortex.locked')" data-status="locked" />
                                @elseif ($agent['override']?->tools !== null)
                                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('overridden')" :label="__('cortex::cortex.overridden')" data-status="overridden" />
                                @else
                                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('from_code')" :label="__('cortex::cortex.from_code')" data-status="from_code" />
                                @endif
                                <span class="text-xs opacity-75">{{ count($agent['tools']) }}</span>
                            </div>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex items-center gap-2">
                                @cortexCan('view', $subject)
                                    <x-atrium::icon-button icon="pencil-square" :label="__('cortex::cortex.manage')" size="sm" variant="outline"
                                                           :href="route('atrium.cortex.concrete-agents.show', $agent['name'])"
                                                           data-testid="manage-{{ $agent['name'] }}" />
                                @endcortexCan
                                @cortexCan('run', $subject)
                                    <x-atrium::icon-button icon="play" :label="__('cortex::cortex.run')" size="sm" variant="outline"
                                                           :href="route('atrium.cortex.run', ['agent' => 'concrete:'.$agent['name']])"
                                                           data-testid="run-{{ $agent['name'] }}" />
                                @endcortexCan
                            </div>
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
