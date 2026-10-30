{{-- The `cortex.servers` list screen: header actions always render; an open
     registered tab's panel replaces the list, and `before`, `after` and each
     server's `row.actions` render only around it. A row is the server's
     instruction override, or an unsaved one when it has none. --}}
<x-atrium::layout :title="__('cortex::cortex.servers')">
    <x-atrium::page-header :title="__('cortex::cortex.servers')">
        @if ($resolved->sections('header.actions') !== [])
            <x-slot:actions>
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </x-slot:actions>
        @endif
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

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
                        @php($subject = \RefactorCircus\Cortex\Atrium\ScreenAccess::serverInstruction($server['name'], $instructions->get($server['name'])))

                        <x-atrium::table.row>
                            <x-atrium::table.cell><code class="text-xs">{{ $server['name'] }}</code></x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @cortexCan('view', $subject)
                                    <x-atrium::icon-button icon="document-text" :label="__('cortex::cortex.instructions')" size="sm" variant="outline"
                                                           :href="route('atrium.cortex.servers.instructions', $server['name'])"
                                                           data-testid="instructions-{{ $server['name'] }}" />
                                @endcortexCan

                                <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$subject" />
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
