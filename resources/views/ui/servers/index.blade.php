<x-atrium::layout :title="__('cortex::cortex.servers')">
    <x-atrium::page-header :title="__('cortex::cortex.servers')" />

    <div class="mt-5">
        @if ($servers === [])
            <x-atrium::empty-state :title="__('cortex::cortex.no_servers')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($servers as $server)
                    <x-atrium::table.row>
                        <x-atrium::table.cell><code class="text-xs">{{ $server['name'] }}</code></x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @cortexCan('view', \JayI\Cortex\Atrium\ScreenAccess::serverInstruction($server['name'], $instructions->get($server['name'])))
                                <x-atrium::icon-button icon="document-text" :label="__('cortex::cortex.instructions')" size="sm" variant="outline"
                                                       :href="route('atrium.cortex.servers.instructions', $server['name'])"
                                                       data-testid="instructions-{{ $server['name'] }}" />
                            @endcortexCan
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
