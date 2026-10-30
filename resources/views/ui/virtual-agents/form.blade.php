@php($editing = $agent !== null)
@php($settings = (array) ($agent?->settings ?? []))
{{-- The form saves through `update` when editing, `create` otherwise; without
     it the agent is shown read-only. --}}
@php($maySave = $editing ? \RefactorCircus\Cortex\Atrium\ScreenAccess::allows('update', $agent) : \RefactorCircus\Cortex\Atrium\ScreenAccess::allows('create', \RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel::class))
{{-- The `cortex.virtual-agent` screen (`cortex.virtual-agent-create` for a new
     agent): header actions always render; an open registered tab's panel
     replaces the form, and `before`, `aside` and `after` render only around
     it, `aside` in a right-hand column only while it has visible sections. --}}
@php($panelOpen = $resolved->openRegisteredTab !== null)
@php($aside = ! $panelOpen && $resolved->sections('aside') !== [])

{{-- Provider and model options are assembled server-side so a value saved
     earlier stays selectable even when the provider no longer lists it.
     Alpine only handles swapping the model when the provider changes. --}}
<x-atrium::layout :title="$editing ? __('cortex::cortex.edit_virtual_agent') : __('cortex::cortex.new_virtual_agent')">
    <x-atrium::page-header :title="$editing ? __('cortex::cortex.edit_virtual_agent') : __('cortex::cortex.new_virtual_agent')">
        <x-slot:actions>
            @if ($editing)
                @cortexCan('run', $agent)
                    <x-atrium::icon-button icon="play" :label="__('cortex::cortex.run')" variant="outline"
                                           :href="route('atrium.cortex.run', ['agent' => 'virtual:'.$agent->slug])" data-testid="run-agent-link" />
                @endcortexCan
            @endif
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_virtual_agents')" variant="ghost"
                                   :href="route('atrium.cortex.virtual-agents.index')" data-testid="back" />
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
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

        <div class="mt-5 flex max-w-2xl flex-col gap-5" x-data="{ expanded: null }">
            <x-atrium::flash :keys="['prompt', 'agent']" />

            <x-atrium::card>
                <form method="POST"
                      action="{{ $editing ? route('atrium.cortex.virtual-agents.update', $agent->slug) : route('atrium.cortex.virtual-agents.store') }}"
                      class="flex flex-col gap-4"
                      x-data="cortexAgentForm({
                          defaults: @js(collect($providers)->mapWithKeys(fn ($provider) => [$provider['name'] => $provider['default_model'] ?? null])),
                          model: @js(old('model', $agent?->model)),
                      })">
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    <fieldset class="flex flex-col gap-4" @disabled(! $maySave)>

                    <x-atrium::form.input name="name" :label="__('cortex::cortex.name')" :value="$agent?->name" required />

                    @unless ($editing)
                        <x-atrium::form.input name="slug" :label="__('cortex::cortex.slug')" :hint="__('cortex::cortex.slug_hint')" required />
                    @endunless

                    <x-atrium::form.textarea name="description" :label="__('cortex::cortex.description')" :value="$agent?->description" />

                    <x-atrium::form.textarea name="instructions" :label="__('cortex::cortex.prompt')"
                                             :hint="__('cortex::cortex.prompt_hint')"
                                             :value="$agent?->publishedVersion?->content"
                                             rows="10" required class="font-mono text-xs" />

                    <x-atrium::form.select
                        name="provider"
                        :label="__('cortex::cortex.provider')"
                        :placeholder="__('cortex::cortex.provider_default')"
                        :options="collect($providerNames)->mapWithKeys(fn ($name) => [$name => $name])"
                        :selected="$agent?->provider"
                        x-model="provider"
                        x-on:change="syncModel()" />

                    <x-atrium::form.select
                        name="model"
                        :label="__('cortex::cortex.model')"
                        :placeholder="__('cortex::cortex.provider_default')"
                        :options="collect($providers)->flatMap(fn ($provider) => $provider['models'] ?? [])->push($agent?->model)->filter()->unique()->mapWithKeys(fn ($model) => [$model => $model])"
                        :selected="$agent?->model"
                        x-model="model" />

                    <x-atrium::section :title="__('cortex::cortex.settings')">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-atrium::form.input type="number" step="0.1" min="0" max="2"
                                                  name="settings[temperature]" :label="__('cortex::cortex.temperature')"
                                                  :value="$settings['temperature'] ?? null" />
                            <x-atrium::form.input type="number" min="1"
                                                  name="settings[max_steps]" :label="__('cortex::cortex.max_steps')"
                                                  :value="$settings['max_steps'] ?? null" />
                            <x-atrium::form.input type="number" min="1"
                                                  name="settings[max_tokens]" :label="__('cortex::cortex.max_tokens')"
                                                  :value="$settings['max_tokens'] ?? null" />
                            <x-atrium::form.input type="number" step="0.05" min="0" max="1"
                                                  name="settings[top_p]" :label="__('cortex::cortex.top_p')"
                                                  :value="$settings['top_p'] ?? null" />
                        </div>
                    </x-atrium::section>

                    @if ($tools !== [])
                        <x-atrium::section :title="__('cortex::cortex.tools')">
                            @include('cortex::ui.partials.tool-picker', [
                                'pickerTools' => collect($tools)->map(fn ($tool) => ['name' => $tool['name'], 'label' => $tool['name'], 'tags' => $tool['tags']])->all(),
                                'checked' => (array) ($agent?->tools ?? []),
                                'idPrefix' => 'tool-',
                            ])
                        </x-atrium::section>
                    @endif

                    @if ($agents->isNotEmpty())
                        <x-atrium::section :title="__('cortex::cortex.sub_agents')">
                            <div class="flex flex-col gap-2">
                                @foreach ($agents as $option)
                                    <x-atrium::form.checkbox
                                        name="sub_agents[]"
                                        :value="$option->slug"
                                        :label="$option->name"
                                        :id="'sub-'.$option->slug"
                                        :checked="in_array($option->slug, $selectedSubAgents, true)" />
                                @endforeach
                            </div>
                        </x-atrium::section>
                    @endif

                    @if ($concreteAgents !== [])
                        <x-atrium::section :title="__('cortex::cortex.concrete_sub_agents')">
                            <div class="flex flex-col gap-2">
                                @foreach ($concreteAgents as $name)
                                    <x-atrium::form.checkbox
                                        name="concrete_sub_agents[]"
                                        :value="$name"
                                        :label="$name"
                                        :id="'concrete-sub-'.$name"
                                        :checked="in_array($name, (array) ($agent?->concrete_sub_agents ?? []), true)" />
                                @endforeach
                            </div>
                        </x-atrium::section>
                    @endif

                    </fieldset>

                    @if ($maySave)
                        <div class="flex items-center gap-2">
                            <x-atrium::icon-button icon="check" :label="__('cortex::cortex.save')" variant="primary" type="submit" data-testid="save-agent" />
                            <x-atrium::icon-button icon="x-mark" :label="__('cortex::cortex.cancel')" variant="ghost"
                                                   :href="route('atrium.cortex.virtual-agents.index')" data-testid="cancel" />
                        </div>
                    @endif
                </form>
            </x-atrium::card>

            @if ($editing && \RefactorCircus\Cortex\Atrium\ScreenAccess::allows('viewAny', \RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel::class, [$agent]))
                @include('cortex::ui.partials.versions', [
                    'versions' => $versions,
                    'publishedVersion' => $agent->publishedVersion?->version,
                    'publishRoute' => fn (int $version) => route('atrium.cortex.virtual-agents.versions.publish', [$agent->slug, $version]),
                ])
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

    @push('atrium-scripts')
        <script>
            window.cortexAgentForm = function (config) {
                return {
                    provider: @js(old('provider', $agent?->provider) ?? ''),
                    model: config.model ?? '',
                    // Choosing a provider moves to its default model, but a
                    // model the user already picked is left alone.
                    syncModel() {
                        const preferred = config.defaults[this.provider]
                        if (preferred && !this.model) this.model = preferred
                    },
                }
            }
        </script>
    @endpush
</x-atrium::layout>
