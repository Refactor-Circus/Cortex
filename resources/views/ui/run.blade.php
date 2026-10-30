{{-- The `cortex.run` screen, with or without a result: header actions always
     render; an open registered tab's panel replaces the form, and `before`,
     `aside` and `after` render only around it, `aside` in a right-hand column
     only while it has visible sections. --}}
@php($panelOpen = $resolved->openRegisteredTab !== null)
@php($aside = ! $panelOpen && $resolved->sections('aside') !== [])

<x-atrium::layout :title="__('cortex::cortex.run_agent')">
    <x-atrium::page-header :title="__('cortex::cortex.run_agent')">
        @if ($resolved->sections('header.actions') !== [])
            <x-slot:actions>
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </x-slot:actions>
        @endif
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

        <div class="mt-5 flex max-w-2xl flex-col gap-5">
            @if ($agents === [])
                <x-atrium::empty-state :title="__('cortex::cortex.no_runnable_agents')" data-testid="no-runnable-agents" />
            @else
            <x-atrium::card>
                <form method="POST" action="{{ route('atrium.cortex.run.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <x-atrium::form.select
                        name="agent"
                        :label="__('cortex::cortex.agents')"
                        :placeholder="__('cortex::cortex.select_agent')"
                        :options="$agents"
                        :selected="$selected"
                        required />

                    <x-atrium::form.textarea name="input" :label="__('cortex::cortex.input')"
                                             :value="$input ?? null" rows="6" required />

                    <div>
                        <x-atrium::icon-button icon="play" :label="__('cortex::cortex.run')" variant="primary" type="submit" data-testid="run-agent" />
                    </div>
                </form>
            </x-atrium::card>
            @endif

            @if ($result)
                <x-atrium::card :title="__('cortex::cortex.response')">
                    <pre class="overflow-x-auto rounded-radius bg-surface-alt p-3 text-xs dark:bg-surface-dark-alt">{{ $result->text }}</pre>

                    @php($usage = $result->usage?->toArray() ?? [])

                    @if ($usage !== [])
                        <div class="mt-4">
                            <h3 class="mb-2 text-sm font-semibold">{{ __('cortex::cortex.usage') }}</h3>

                            <x-atrium::table compact>
                                @foreach ($usage as $key => $value)
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell>{{ $key }}</x-atrium::table.cell>
                                        <x-atrium::table.cell numeric>{{ is_scalar($value) ? $value : json_encode($value) }}</x-atrium::table.cell>
                                    </x-atrium::table.row>
                                @endforeach
                            </x-atrium::table>
                        </div>
                    @endif
                </x-atrium::card>
            @endif
        </div>

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
