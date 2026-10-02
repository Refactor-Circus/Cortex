<x-atrium::layout :title="__('cortex::cortex.virtual_agents')">
    <x-atrium::page-header :title="__('cortex::cortex.virtual_agents')">
        <x-slot:actions>
            @cortexCan('create', \JayI\Cortex\Models\VirtualAgent::class)
                <x-atrium::icon-button icon="plus" :label="__('cortex::cortex.new_virtual_agent')" variant="primary"
                                       :href="route('atrium.cortex.virtual-agents.create')" data-testid="new-agent" />
            @endcortexCan
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5">
        @include('cortex::ui.partials.status')

        @if ($agents->isEmpty())
            <x-atrium::empty-state :title="__('cortex::cortex.no_agents')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.slug') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.prompt') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.provider') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.model') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($agents as $agent)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            @cortexCan('view', $agent)
                                <a class="font-medium underline-offset-2 hover:underline"
                                   href="{{ route('atrium.cortex.virtual-agents.edit', $agent->slug) }}">{{ $agent->name }}</a>
                            @else
                                <span class="font-medium">{{ $agent->name }}</span>
                            @endcortexCan
                        </x-atrium::table.cell>
                        <x-atrium::table.cell><code class="text-xs">{{ $agent->slug }}</code></x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $agent->publishedVersion ? 'v'.$agent->publishedVersion->version : '—' }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $agent->provider ?? '—' }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $agent->model ?? '—' }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex items-center gap-2">
                                @cortexCan('view', $agent)
                                    <x-atrium::icon-button icon="pencil-square" :label="__('cortex::cortex.edit')" size="sm" variant="outline"
                                                           :href="route('atrium.cortex.virtual-agents.edit', $agent->slug)"
                                                           data-testid="edit-{{ $agent->slug }}" />
                                @endcortexCan

                                @cortexCan('run', $agent)
                                    <x-atrium::icon-button icon="play" :label="__('cortex::cortex.run')" size="sm" variant="outline"
                                                           :href="route('atrium.cortex.run', ['agent' => 'virtual:'.$agent->slug])"
                                                           data-testid="run-{{ $agent->slug }}" />
                                @endcortexCan

                                @cortexCan('delete', $agent)
                                <form method="POST" action="{{ route('atrium.cortex.virtual-agents.destroy', $agent->slug) }}"
                                      onsubmit="return confirm(@js(__('cortex::cortex.confirm_delete')))">
                                    @csrf
                                    @method('DELETE')
                                    <x-atrium::icon-button icon="trash" :label="__('cortex::cortex.delete')" size="sm" variant="danger" type="submit"
                                                           data-testid="delete-{{ $agent->slug }}" />
                                </form>
                                @endcortexCan
                            </div>
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <div class="mt-4">
                <x-atrium::pagination :paginator="$agents" />
            </div>
        @endif
    </div>
</x-atrium::layout>
