{{-- The `cortex.server-instructions` screen: header actions always render;
     an open registered tab's panel replaces the editors, and `before`,
     `aside` and `after` render only around them, `aside` in a right-hand
     column only while it has visible sections. --}}
@php($panelOpen = $resolved->openRegisteredTab !== null)
@php($aside = ! $panelOpen && $resolved->sections('aside') !== [])

<x-atrium::layout :title="__('cortex::cortex.server_instructions')">
    <x-atrium::page-header :title="$server" :description="__('cortex::cortex.server_instructions')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_servers')" variant="ghost"
                                   :href="route('atrium.cortex.servers.index')" data-testid="back" />
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($panelOpen)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        @if ($aside)
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
        @endif

        @include('cortex::ui.partials.override', [
            'override' => $instruction,
            'subject' => $subject,
            'versionClass' => \RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel::class,
            'fallback' => $codeInstructions,
            'versions' => $versions,
            'storeRoute' => route('atrium.cortex.servers.instructions.store', $server),
            'destroyRoute' => route('atrium.cortex.servers.instructions.destroy', $server),
            'publishRoute' => fn (int $version) => route('atrium.cortex.servers.instructions.publish', [$server, $version]),
        ])

        {{-- No tool editor for a server whose tools cannot be overridden (the cortex server). --}}
        @if ($toolEditor !== null)
            @include('cortex::ui.servers.partials.tools', [
                'server' => $server,
                'subject' => $subject,
                'toolEditor' => $toolEditor,
            ])
        @endif

        @if ($aside)
                </div>

                <aside class="mt-5 flex flex-col gap-4" data-testid="screen-aside">
                    <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                </aside>
            </div>
        @endif

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
