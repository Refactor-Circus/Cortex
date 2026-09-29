{{-- Shared by the tool-description, server-instruction and concrete agent
     prompt editors, which are the same page over different subjects. $override is null when nothing has
     been overridden and the value declared in code is still in force. --}}
<div class="mt-5 flex flex-col gap-5" x-data="{ expanded: null }">
    @include('cortex::ui.partials.status')

    <x-atrium::card :title="$liveTitle ?? __('cortex::cortex.live_description')">
        <div class="mb-3">
            @if ($override?->publishedVersion)
                <x-atrium::badge variant="success">
                    {{ __('cortex::cortex.override', ['version' => $override->publishedVersion->version]) }}
                </x-atrium::badge>
                <p class="mt-2 text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.override_hint') }}</p>
            @else
                <x-atrium::badge>{{ __('cortex::cortex.from_code') }}</x-atrium::badge>
                <p class="mt-2 text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.no_override_hint') }}</p>
            @endif
        </div>

        <pre class="overflow-x-auto rounded-radius bg-surface-alt p-3 text-xs dark:bg-surface-dark-alt">{{ $override?->publishedVersion?->content ?? $fallback }}</pre>
    </x-atrium::card>

    @include('cortex::ui.partials.versions', [
        'versions' => $versions,
        'publishedVersion' => $override?->publishedVersion?->version,
        'publishRoute' => $publishRoute,
    ])

    <x-atrium::card :title="__('cortex::cortex.new_version')">
        <form method="POST" action="{{ $storeRoute }}" class="flex flex-col gap-4">
            @csrf
            <x-atrium::form.textarea name="content" :label="__('cortex::cortex.content')"
                                     :value="$override?->publishedVersion?->content ?? $fallback"
                                     rows="8" required class="font-mono text-xs" />
            <x-atrium::form.checkbox name="publish" :label="__('cortex::cortex.publish_immediately')" :checked="true" />

            <div>
                <x-atrium::button type="submit" data-testid="add-version">{{ __('cortex::cortex.add_version') }}</x-atrium::button>
            </div>
        </form>
    </x-atrium::card>

    @if ($override)
        <x-atrium::card :title="__('cortex::cortex.remove_override')">
            <form method="POST" action="{{ $destroyRoute }}"
                  onsubmit="return confirm(@js(__('cortex::cortex.confirm_delete')))">
                @csrf
                @method('DELETE')
                <x-atrium::button variant="danger" type="submit" data-testid="remove-override">
                    {{ __('cortex::cortex.remove_override') }}
                </x-atrium::button>
            </form>
        </x-atrium::card>
    @endif
</div>
