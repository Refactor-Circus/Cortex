{{-- Shared by the tool-description, server-instruction and concrete agent
     prompt editors, which are the same page over different subjects. $override is null when nothing has
     been overridden and the value declared in code is still in force; $subject is the override, or an
     unsaved one, that the policies are asked about, and $versionClass its version model. Each card shows
     only when the action behind it would be allowed. --}}
<div class="mt-5 flex flex-col gap-5" x-data="{ expanded: null }">
    <x-atrium::flash :keys="['prompt', 'agent']" />

    <x-atrium::card :title="$liveTitle ?? __('cortex::cortex.live_description')">
        <div class="mb-3 flex items-center gap-2">
            @if ($override?->publishedVersion)
                <x-atrium::status-dot :variant="\RefactorCircus\Cortex\Atrium\Badges::forStatus('overridden')" :label="__('cortex::cortex.override', ['version' => $override->publishedVersion->version])" data-status="overridden" />
                <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.override_hint') }}</p>
            @else
                <x-atrium::status-dot :variant="\RefactorCircus\Cortex\Atrium\Badges::forStatus('from_code')" :label="__('cortex::cortex.from_code')" data-status="from_code" />
                <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.no_override_hint') }}</p>
            @endif
        </div>

        <pre class="overflow-x-auto rounded-radius bg-surface-alt p-3 text-xs dark:bg-surface-dark-alt">{{ $override?->publishedVersion?->content ?? $fallback }}</pre>
    </x-atrium::card>

    @cortexCan('viewAny', $versionClass, [$subject])
        @include('cortex::ui.partials.versions', [
            'versions' => $versions,
            'publishedVersion' => $override?->publishedVersion?->version,
            'publishRoute' => $publishRoute,
        ])
    @endcortexCan

    @cortexCan('create', $versionClass, [$subject])
    <x-atrium::card :title="__('cortex::cortex.new_version')" data-testid="new-version-card">
        <form method="POST" action="{{ $storeRoute }}" class="flex flex-col gap-4">
            @csrf
            <x-atrium::form.textarea name="content" :label="__('cortex::cortex.content')"
                                     :value="$override?->publishedVersion?->content ?? $fallback"
                                     rows="8" required class="font-mono text-xs" />
            <x-atrium::form.checkbox name="publish" :label="__('cortex::cortex.publish_immediately')" :checked="true" />

            <div>
                <x-atrium::icon-button icon="document-plus" :label="__('cortex::cortex.add_version')" variant="primary" type="submit" data-testid="add-version" />
            </div>
        </form>
    </x-atrium::card>
    @endcortexCan

    @if ($override && \RefactorCircus\Cortex\Atrium\ScreenAccess::allows('delete', $override))
        <x-atrium::card :title="__('cortex::cortex.remove_override')">
            <form method="POST" action="{{ $destroyRoute }}"
                  onsubmit="return confirm(@js(__('cortex::cortex.confirm_delete')))">
                @csrf
                @method('DELETE')
                <x-atrium::icon-button icon="trash" :label="__('cortex::cortex.remove_override')" variant="danger" type="submit" data-testid="remove-override" />
            </form>
        </x-atrium::card>
    @endif

    {{-- The override's own history; versions above are its publishable content. --}}
    @if ($override)
        <x-atrium::audit-trail source="cortex" :subject="$override" />
    @endif
</div>
