{{-- A checkbox list of tools narrowed in the browser by tag, name and
     selection. Filtering only hides rows, so a checked tool stays in the
     submitted form while it is out of view.

     Expects $pickerTools (list of {name, label, tags}), $checked (list of
     tool names) and $idPrefix. --}}
@php($pickerTags = collect($pickerTools)->pluck('tags')->flatten()->unique()->sort()->values()->all())

<div class="flex flex-col gap-3"
     x-data="{
         tag: null,
         search: '',
         selectedOnly: false,
         selected: 0,
         count() { this.selected = $root.querySelectorAll('input[type=checkbox][data-tool]:checked').length },
         shows(el) {
             const box = el.querySelector('input[data-tool]')
             return (this.tag === null || JSON.parse(el.dataset.tags).includes(this.tag))
                 && (this.search === '' || box.value.toLowerCase().includes(this.search.toLowerCase()))
                 && (! this.selectedOnly || box.checked)
         },
     }"
     x-init="count()"
     x-on:change="count()"
     data-testid="tool-picker">
    <div class="flex flex-wrap items-center gap-2">
        <x-atrium::search-input x-model="search" :placeholder="__('cortex::cortex.search_tools')" data-testid="tool-search" />

        {{-- A view filter, not a form field: it has no name, so it is never submitted. --}}
        <label class="flex cursor-pointer items-center gap-2 text-sm">
            <x-atrium::form.checkbox bare name="" :id="$idPrefix.'selected-only'" x-model="selectedOnly" data-testid="selected-only" />
            {{ __('cortex::cortex.selected_only') }}
        </label>

        <span class="ml-auto text-xs opacity-75" x-text="@js(__('cortex::cortex.tools_selected', ['count' => '__COUNT__'])).replace('__COUNT__', selected)"></span>
    </div>

    @if ($pickerTags !== [])
        <div class="flex flex-wrap gap-2" data-testid="tool-tags">
            <x-atrium::chip :active="true" x-on:click="tag = null" x-bind:aria-pressed="tag === null" data-testid="picker-tag-all">
                {{ __('cortex::cortex.all_tags') }}
            </x-atrium::chip>
            @foreach ($pickerTags as $pickerTag)
                <x-atrium::chip x-on:click="tag = tag === {{ Js::from($pickerTag) }} ? null : {{ Js::from($pickerTag) }}"
                                x-bind:aria-pressed="tag === {{ Js::from($pickerTag) }}" data-testid="picker-tag-{{ $pickerTag }}">
                    {{ $pickerTag }}
                </x-atrium::chip>
            @endforeach
        </div>
    @endif

    <div class="flex max-h-80 flex-col gap-2 overflow-y-auto">
        @foreach ($pickerTools as $pickerTool)
            <div class="flex flex-wrap items-center gap-2" data-tags="{{ json_encode($pickerTool['tags']) }}" x-show="shows($el)">
                <x-atrium::form.checkbox
                    name="tools[]"
                    wrapper="shrink-0"
                    data-tool
                    :value="$pickerTool['name']"
                    :label="$pickerTool['label']"
                    :id="$idPrefix.$pickerTool['name']"
                    :checked="in_array($pickerTool['name'], $checked, true)" />
                @foreach ($pickerTool['tags'] as $toolTag)
                    <x-atrium::badge>{{ $toolTag }}</x-atrium::badge>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
