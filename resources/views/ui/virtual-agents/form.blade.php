@php($editing = $agent !== null)
@php($settings = (array) ($agent?->settings ?? []))
{{-- The form saves through `update` when editing, `create` otherwise; without
     it the agent is shown read-only. --}}
@php($maySave = $editing ? \JayI\Cortex\Atrium\ScreenAccess::allows('update', $agent) : \JayI\Cortex\Atrium\ScreenAccess::allows('create', \JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel::class))

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
        </x-slot:actions>
    </x-atrium::page-header>

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
                                    :checked="in_array($option->slug, $agent?->subAgents?->pluck('slug')->all() ?? [], true)" />
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

        @if ($editing && \JayI\Cortex\Atrium\ScreenAccess::allows('viewAny', \JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel::class, [$agent]))
            @include('cortex::ui.partials.versions', [
                'versions' => $versions,
                'publishedVersion' => $agent->publishedVersion?->version,
                'publishRoute' => fn (int $version) => route('atrium.cortex.virtual-agents.versions.publish', [$agent->slug, $version]),
            ])
        @endif

        @if ($editing)
            <x-atrium::audit-trail source="cortex" :subject="$agent" />
        @endif
    </div>

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
