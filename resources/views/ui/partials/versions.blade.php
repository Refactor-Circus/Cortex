{{-- Version history with view/hide and publish controls. Shared by every
     versioned subject: virtual agent prompts and the tool, server and
     concrete agent overrides. Needs an `expanded` Alpine scope around it. --}}
<x-atrium::card :title="__('cortex::cortex.versions')">
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
                        v{{ $version->version }}
                        @if ($isPublished)
                            <x-atrium::badge variant="success">{{ __('cortex::cortex.published') }}</x-atrium::badge>
                        @endif
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>{{ $version->created_at?->diffForHumans() }}</x-atrium::table.cell>
                    <x-atrium::table.cell>
                        <div class="flex items-center gap-2">
                            {{-- Two buttons rather than one with x-text, so each
                                 label is real markup that exists before Alpine boots. --}}
                            <x-atrium::button size="sm" variant="ghost"
                                              x-show="expanded !== {{ $version->version }}"
                                              x-on:click="expanded = {{ $version->version }}">
                                {{ __('cortex::cortex.view') }}
                            </x-atrium::button>

                            <x-atrium::button size="sm" variant="ghost"
                                              x-show="expanded === {{ $version->version }}" x-cloak
                                              x-on:click="expanded = null">
                                {{ __('cortex::cortex.hide') }}
                            </x-atrium::button>

                            @unless ($isPublished)
                                <form method="POST" action="{{ $publishRoute($version->version) }}">
                                    @csrf
                                    <x-atrium::button size="sm" variant="outline" type="submit"
                                                      data-testid="publish-{{ $version->version }}">
                                        {{ __('cortex::cortex.publish') }}
                                    </x-atrium::button>
                                </form>
                            @endunless
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
