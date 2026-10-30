{{--
    The workbench test host (TestHostScreenController), laid out as Atrium
    lays out its own screens: `header.actions` always renders in the page
    header's actions; an open registered tab's panel replaces the page
    content; otherwise `before` and `after` render around the content, and
    `aside` gets a right-hand column only when it has visible sections.
--}}
@php($screen = $resolved->screen)
@php($panelOpen = $resolved->openRegisteredTab !== null)
@php($aside = ! $panelOpen && $resolved->sections('aside') !== [])

<x-atrium::layout :title="$screen->key">
    <x-atrium::page-header :title="$screen->key" :description="'Workbench test host for '.$screen->key.'.'">
        <x-slot:actions>
            <x-atrium::badge data-testid="host-action">Host action</x-atrium::badge>
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($panelOpen)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        <div @class(['mt-5', 'grid gap-6 lg:grid-cols-3' => $aside])>
            <x-atrium::card :class="$aside ? 'lg:col-span-2' : ''" data-testid="test-host-content">
                <p>{{ $screen->record?->getAttribute('name') ?? 'New record' }}</p>
            </x-atrium::card>

            @if ($aside)
                <aside class="flex flex-col gap-4" data-testid="screen-aside">
                    <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                </aside>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
