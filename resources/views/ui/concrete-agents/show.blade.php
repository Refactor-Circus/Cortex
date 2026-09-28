@php($toolsOverridden = $override?->tools !== null)
@php($checkedTools = $toolsOverridden ? $override->tools : $agent['default_tools'])

<x-atrium::layout :title="$agent['name']">
    <x-atrium::page-header :title="$agent['name']" :description="$agent['class']">
        <x-slot:actions>
            <x-atrium::button variant="outline" :href="route('atrium.cortex.run', ['agent' => 'concrete:'.$agent['name']])">
                {{ __('cortex::cortex.run') }}
            </x-atrium::button>
            <x-atrium::button variant="ghost" :href="route('atrium.cortex.concrete-agents.index')">
                {{ __('cortex::cortex.back_to_concrete_agents') }}
            </x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    @unless ($agent['overridable'])
        <div class="mt-5">
            <x-atrium::alert variant="warning">{{ __('cortex::cortex.not_overridable') }}</x-atrium::alert>
        </div>
    @endunless

    @include('cortex::ui.partials.override', [
        'override' => $override,
        'fallback' => $agent['default_instructions'],
        'liveTitle' => __('cortex::cortex.live_prompt'),
        'versions' => $versions,
        'storeRoute' => route('atrium.cortex.concrete-agents.store', $agent['name']),
        'destroyRoute' => route('atrium.cortex.concrete-agents.destroy', $agent['name']),
        'publishRoute' => fn (int $version) => route('atrium.cortex.concrete-agents.publish', [$agent['name'], $version]),
    ])

    <div class="mt-5">
        <x-atrium::card :title="__('cortex::cortex.tools')">
            <div class="mb-3">
                @if (! $agent['tools_overridable'])
                    <x-atrium::badge>{{ __('cortex::cortex.locked') }}</x-atrium::badge>
                    @if ($agent['overridable'])
                        <p class="mt-2 text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_locked_hint') }}</p>
                    @endif
                @elseif ($toolsOverridden)
                    <x-atrium::badge variant="success">{{ __('cortex::cortex.overridden') }}</x-atrium::badge>
                    <p class="mt-2 text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_override_hint') }}</p>
                @else
                    <x-atrium::badge>{{ __('cortex::cortex.from_code') }}</x-atrium::badge>
                    <p class="mt-2 text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_no_override_hint') }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('atrium.cortex.concrete-agents.tools', $agent['name']) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                @if (! $agent['tools_overridable'])
                    <ul class="flex flex-col gap-1 text-sm">
                        @foreach ($agent['default_tools'] as $tool)
                            <li><code class="text-xs">{{ $tool }}</code></li>
                        @endforeach
                    </ul>
                @elseif ($availableTools === [])
                    <x-atrium::empty-state :title="__('cortex::cortex.no_tools_available')" />
                @else
                    <div class="flex flex-col gap-2">
                        @foreach ($availableTools as $tool)
                            <x-atrium::form.checkbox
                                name="tools[]"
                                :value="$tool"
                                :label="in_array($tool, $agent['default_tools'], true) ? $tool.' ('.__('cortex::cortex.from_code').')' : $tool"
                                :id="'tool-'.$tool"
                                :checked="in_array($tool, $checkedTools, true)" />
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    @if ($agent['tools_overridable'])
                        <x-atrium::button type="submit" data-testid="save-tools">{{ __('cortex::cortex.save_tools') }}</x-atrium::button>
                    @endif

                    @if ($toolsOverridden)
                        <x-atrium::button type="submit" variant="ghost" name="use_code_tools" value="1" data-testid="reset-tools">
                            {{ __('cortex::cortex.use_code_tools') }}
                        </x-atrium::button>
                    @endif
                </div>
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
