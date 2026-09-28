<x-atrium::layout :title="__('cortex::cortex.run_agent')">
    <x-atrium::page-header :title="__('cortex::cortex.run_agent')" />

    <div class="mt-5 flex max-w-2xl flex-col gap-5">
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
                    <x-atrium::button type="submit" data-testid="run-agent">{{ __('cortex::cortex.run') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

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
</x-atrium::layout>
