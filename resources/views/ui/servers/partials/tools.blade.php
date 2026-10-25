{{-- The tool override editor of a concrete server's screen. Expects $server,
     $subject (the override, or an unsaved one, the policy is asked about) and
     $toolEditor ({overridden, checked, served, items}); the screen leaves it
     out for a server whose tools cannot be overridden.

     The save form posts `tools[]` (one checkbox per item, in checklist order),
     `confirm_empty` and a `save` button; restoring the code tools is its own
     form with a `restore` button. --}}
@php($mayUpdateTools = \RefactorCircus\Cortex\Atrium\ScreenAccess::allows('updateTools', $subject))
@php($toolsError = ($errors ?? null)?->first('tools') ?: ($errors ?? null)?->first('tools.*') ?: ($errors ?? null)?->first('server'))

<div class="mt-5">
    <x-atrium::card :title="__('cortex::cortex.tools')" data-testid="server-tools-card">
        <div class="mb-3 flex items-center gap-2">
            @if ($toolEditor['overridden'])
                <x-atrium::status-dot :variant="\RefactorCircus\Cortex\Atrium\Badges::forStatus('overridden')" :label="__('cortex::cortex.overridden')" data-status="overridden" />
                <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.server_tools_override_hint') }}</p>
            @else
                <x-atrium::status-dot :variant="\RefactorCircus\Cortex\Atrium\Badges::forStatus('from_code')" :label="__('cortex::cortex.from_code')" data-status="from_code" />
                <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('cortex::cortex.server_tools_no_override_hint') }}</p>
            @endif
        </div>

        @if ($toolsError)
            <div class="mb-3">
                <x-atrium::alert variant="danger" data-testid="server-tools-error">{{ $toolsError }}</x-atrium::alert>
            </div>
        @endif

        @if (! $mayUpdateTools)
            @if ($toolEditor['served'] === [])
                <x-atrium::empty-state :title="__('cortex::cortex.server_serves_no_tools')" />
            @else
                <ul class="flex flex-col gap-1 text-sm" data-testid="server-tools-served">
                    @foreach ($toolEditor['served'] as $tool)
                        <li><code class="text-xs">{{ $tool }}</code></li>
                    @endforeach
                </ul>
            @endif
        @else
            <form method="POST" action="{{ route('atrium.cortex.servers.tools.update', $server) }}" class="flex flex-col gap-4" data-testid="server-tools-form">
                @csrf
                @method('PUT')

                @if ($toolEditor['items'] === [])
                    <x-atrium::empty-state :title="__('cortex::cortex.no_tools_available')" />
                @else
                    @include('cortex::ui.partials.tool-picker', [
                        'pickerTools' => $toolEditor['items'],
                        'checked' => $toolEditor['checked'],
                        'idPrefix' => 'server-tool-',
                    ])
                @endif

                <x-atrium::form.checkbox name="confirm_empty" value="1" :label="__('cortex::cortex.confirm_empty_tools')"
                                         :hint="__('cortex::cortex.confirm_empty_tools_hint')" accent="danger" data-testid="confirm-empty-tools" />

                <div class="flex items-center gap-2">
                    <x-atrium::icon-button icon="check" :label="__('cortex::cortex.save_tools')" variant="primary" type="submit"
                                           name="save" value="1" data-testid="save-server-tools" />
                </div>
            </form>

            @if ($toolEditor['overridden'])
                <form method="POST" action="{{ route('atrium.cortex.servers.tools.destroy', $server) }}" class="mt-4"
                      onsubmit="return confirm(@js(__('cortex::cortex.confirm_restore_code_tools')))">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="arrow-uturn-left" :label="__('cortex::cortex.restore_code_tools')" variant="ghost" type="submit"
                                           name="restore" value="1" data-testid="restore-server-tools" />
                </form>
            @endif
        @endif
    </x-atrium::card>
</div>
