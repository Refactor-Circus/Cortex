{{-- Version history with view/hide and publish controls. Shared by every
     versioned subject: virtual agent prompts and the tool, server and
     concrete agent overrides. Needs an `expanded` Alpine scope around it.
     Publish shows only to those the version's policy lets publish it. --}}
<x-atrium::card :title="__('cortex::cortex.versions')" data-testid="versions-card">
    @if ($versions->isEmpty())
        <x-atrium::empty-state :title="__('cortex::cortex.no_versions')" />
    @else
        <x-atrium::table>
            <x-slot:head>
                <x-atrium::table.row>
                    <x-atrium::table.cell heading>{{ __('cortex::cortex.version') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('cortex::cortex.created') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('cortex::cortex.actions') }}</x-atrium::table.cell>
                </x-atrium::table.row>
            </x-slot:head>

            @foreach ($versions as $version)
                @php($isPublished = $publishedVersion === $version->version)

                <x-atrium::table.row>
                    <x-atrium::table.cell>
                        <div class="flex items-center gap-2">
                            v{{ $version->version }}
                            @if ($isPublished)
                                <x-atrium::status-dot :variant="\JayI\Cortex\Atrium\Badges::forStatus('published')" :label="__('cortex::cortex.published')" data-status="published" />
                            @endif
                        </div>
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>{{ $version->created_at?->diffForHumans() }}</x-atrium::table.cell>
                    <x-atrium::table.cell>
                        <div class="flex items-center gap-2">
                            {{-- Two buttons rather than one toggling its icon, so
                                 each label is real markup that exists before Alpine boots. --}}
                            <span class="inline-flex" x-show="expanded !== {{ $version->version }}">
                                <x-atrium::icon-button icon="eye" :label="__('cortex::cortex.view')" size="sm" variant="ghost"
                                                       x-on:click="expanded = {{ $version->version }}"
                                                       data-testid="view-{{ $version->version }}" />
                            </span>

                            <span class="inline-flex" x-show="expanded === {{ $version->version }}" x-cloak>
                                <x-atrium::icon-button icon="eye-slash" :label="__('cortex::cortex.hide')" size="sm" variant="ghost"
                                                       x-on:click="expanded = null"
                                                       data-testid="hide-{{ $version->version }}" />
                            </span>

                            @if (! $isPublished && \JayI\Cortex\Http\Ui\ScreenAccess::allows('publish', $version))
                                <form method="POST" action="{{ $publishRoute($version->version) }}">
                                    @csrf
                                    <x-atrium::icon-button icon="check-badge" :label="__('cortex::cortex.publish')" size="sm" variant="outline" type="submit"
                                                           data-testid="publish-{{ $version->version }}" />
                                </form>
                            @endif
                        </div>
                    </x-atrium::table.cell>
                </x-atrium::table.row>

                <x-atrium::table.row x-show="expanded === {{ $version->version }}" x-cloak>
                    <x-atrium::table.cell colspan="3">
                        <pre class="overflow-x-auto rounded-radius bg-surface-alt p-3 text-xs dark:bg-surface-dark-alt">{{ $version->content }}</pre>
                    </x-atrium::table.cell>
                </x-atrium::table.row>
            @endforeach
        </x-atrium::table>
    @endif
</x-atrium::card>
