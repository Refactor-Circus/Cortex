{{-- The `cortex.tool-description` screen: header actions always render; an
     open registered tab's panel replaces the editor, and `before`, `aside`
     and `after` render only around it, `aside` in a right-hand column only
     while it has visible sections. --}}
@php($panelOpen = $resolved->openRegisteredTab !== null)
@php($aside = ! $panelOpen && $resolved->sections('aside') !== [])

<x-atrium::layout :title="__('cortex::cortex.tool_description')">
    <x-atrium::page-header :title="$tool" :description="__('cortex::cortex.tool_description')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_tools')" variant="ghost"
                                   :href="route('atrium.cortex.tools.index')" data-testid="back" />
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
            'override' => $description,
            'subject' => $subject,
            'versionClass' => \RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel::class,
            'fallback' => $codeDescription,
            'versions' => $versions,
            'storeRoute' => route('atrium.cortex.tools.description.store', $tool),
            'destroyRoute' => route('atrium.cortex.tools.description.destroy', $tool),
            'publishRoute' => fn (int $version) => route('atrium.cortex.tools.description.publish', [$tool, $version]),
        ])

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
