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
        <input type="search" x-model="search" placeholder="{{ __('cortex::cortex.search_tools') }}"
               class="w-full max-w-sm rounded-radius border border-outline bg-surface-alt px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark-alt/50" />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" x-model="selectedOnly" class="size-4 accent-primary dark:accent-primary-dark" />
            {{ __('cortex::cortex.selected_only') }}
        </label>

        <span class="ml-auto text-xs opacity-75" x-text="@js(__('cortex::cortex.tools_selected', ['count' => '__COUNT__'])).replace('__COUNT__', selected)"></span>
    </div>

    @if ($pickerTags !== [])
        <div class="flex flex-wrap gap-2" data-testid="tool-tags">
            <button type="button" x-on:click="tag = null"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    x-bind:class="tag === null ? 'bg-primary text-on-primary dark:bg-primary-dark dark:text-on-primary-dark' : 'bg-surface-alt text-on-surface dark:bg-surface-dark-alt dark:text-on-surface-dark'">
                {{ __('cortex::cortex.all_tags') }}
            </button>
            @foreach ($pickerTags as $pickerTag)
                <button type="button" x-on:click="tag = tag === @js($pickerTag) ? null : @js($pickerTag)"
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        x-bind:class="tag === @js($pickerTag) ? 'bg-primary text-on-primary dark:bg-primary-dark dark:text-on-primary-dark' : 'bg-surface-alt text-on-surface dark:bg-surface-dark-alt dark:text-on-surface-dark'">
                    {{ $pickerTag }}
                </button>
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
