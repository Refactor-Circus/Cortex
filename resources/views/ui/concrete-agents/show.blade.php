@php($toolsOverridden = $override?->tools !== null)
@php($checkedTools = $toolsOverridden ? $override->tools : $agent['default_tools'])

<x-atrium::layout :title="$agent['name']">
    <x-atrium::page-header :title="$agent['name']" :description="$agent['class']">
        <x-slot:actions>
            @cortexCan('run', $subject)
                <x-atrium::icon-button icon="play" :label="__('cortex::cortex.run')" variant="outline"
                                       :href="route('atrium.cortex.run', ['agent' => 'concrete:'.$agent['name']])" data-testid="run-agent-link" />
            @endcortexCan
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_concrete_agents')" variant="ghost"
                                   :href="route('atrium.cortex.concrete-agents.index')" data-testid="back" />
        </x-slot:actions>
    </x-atrium::page-header>

    @unless ($agent['overridable'])
        <div class="mt-5">
            <x-atrium::alert variant="warning">{{ __('cortex::cortex.not_overridable') }}</x-atrium::alert>
        </div>
    @endunless

    @include('cortex::ui.partials.override', [
        'override' => $override,
        'subject' => $subject,
        'versionClass' => \JayI\Cortex\Models\ConcreteAgentOverrideVersion::class,
        'fallback' => $agent['default_instructions'],
        'liveTitle' => __('cortex::cortex.live_prompt'),
        'versions' => $versions,
        'storeRoute' => route('atrium.cortex.concrete-agents.store', $agent['name']),
        'destroyRoute' => route('atrium.cortex.concrete-agents.destroy', $agent['name']),
        'publishRoute' => fn (int $version) => route('atrium.cortex.concrete-agents.publish', [$agent['name'], $version]),
    ])

    <div class="mt-5">
        <x-atrium::card :title="__('cortex::cortex.tools')">
            <div class="mb-3 flex items-center gap-2">
                @if (! $agent['tools_overridable'])
                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('locked')" :label="__('cortex::cortex.locked')" data-status="locked" />
                    @if ($agent['overridable'])
                        <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_locked_hint') }}</p>
                    @endif
                @elseif ($toolsOverridden)
                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('overridden')" :label="__('cortex::cortex.overridden')" data-status="overridden" />
                    <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_override_hint') }}</p>
                @else
                    <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('from_code')" :label="__('cortex::cortex.from_code')" data-status="from_code" />
                    <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.tools_no_override_hint') }}</p>
                @endif
            </div>

            @php($mayUpdateTools = \JayI\Cortex\Http\Ui\ScreenAccess::allows('update', $subject))

            <form method="POST" action="{{ route('atrium.cortex.concrete-agents.tools', $agent['name']) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                @if (! $agent['tools_overridable'] || ! $mayUpdateTools)
                    <ul class="flex flex-col gap-1 text-sm">
                        @foreach ($agent['tools_overridable'] ? $checkedTools : $agent['default_tools'] as $tool)
                            <li><code class="text-xs">{{ $tool }}</code></li>
                        @endforeach
                    </ul>
                @elseif ($availableTools === [])
                    <x-atrium::empty-state :title="__('cortex::cortex.no_tools_available')" />
                @else
                    @include('cortex::ui.partials.tool-picker', [
                        'pickerTools' => collect($availableTools)->map(fn ($tool) => [
                            'name' => $tool['name'],
                            'label' => in_array($tool['name'], $agent['default_tools'], true) ? $tool['name'].' ('.__('cortex::cortex.from_code').')' : $tool['name'],
                            'tags' => $tool['tags'],
                        ])->all(),
                        'checked' => $checkedTools,
                        'idPrefix' => 'tool-',
                    ])
                @endif

                @if ($mayUpdateTools)
                    <div class="flex items-center gap-2">
                        @if ($agent['tools_overridable'])
                            <x-atrium::icon-button icon="check" :label="__('cortex::cortex.save_tools')" variant="primary" type="submit" data-testid="save-tools" />
                        @endif

                        @if ($toolsOverridden)
                            <x-atrium::icon-button icon="arrow-uturn-left" :label="__('cortex::cortex.use_code_tools')" variant="ghost" type="submit"
                                                   name="use_code_tools" value="1" data-testid="reset-tools" />
                        @endif
                    </div>
                @endif
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
