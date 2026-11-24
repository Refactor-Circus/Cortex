<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import { cortexData, useCortexLoad, type CortexList, type CortexTool } from '../../components/cortex'
import CortexLoad from '../../components/CortexLoad.vue'
import CortexPage from '../../components/CortexPage.vue'

/**
 * `cortex.tools`: the registered tools (`tools.index`), narrowed by tag as
 * the v1 chips did (`?tag=`, sent to the API) and by name, each linking to
 * its description.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const tag = computed(() => typeof route.query.tag === 'string' && route.query.tag !== '' ? route.query.tag : null)
const search = ref('')

const { data, pending, failed, status, reload } = useCortexLoad(async cortex => ({
  tools: cortexData<CortexList<CortexTool>>(await cortex.GET('/tools', { params: { query: tag.value === null ? {} : { tag: tag.value } } })).data,
  tags: tag.value === null
    ? null
    : cortexData<CortexList<CortexTool>>(await cortex.GET('/tools')).data.flatMap(tool => tool.tags)
}))

watch(tag, () => void reload())

const tags = computed(() => [...new Set(data.value?.tags ?? (data.value?.tools ?? []).flatMap(tool => tool.tags))].sort())

const tools = computed(() => (data.value?.tools ?? [])
  .filter(tool => search.value === '' || tool.name.toLowerCase().includes(search.value.toLowerCase())))

const columns = computed<TableColumn<CortexTool>[]>(() => [
  { accessorKey: 'name', header: t('cortex.agent.name') },
  { accessorKey: 'description', header: t('cortex.tools.description') },
  { id: 'tags', header: t('cortex.tools.tags') },
  { id: 'actions', header: t('cortex.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

function choose(option: string | null): void {
  void router.push({ query: { ...route.query, tag: option ?? undefined } })
}
</script>

<template>
  <CortexPage :title="t('cortex.tools.title')" screen-key="cortex.tools">
    <CortexLoad :ready="!pending" :failed="failed" :status="status">
      <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2" data-testid="cortex-tags">
          <UButton size="xs" color="neutral" :variant="tag === null ? 'solid' : 'outline'" :label="t('cortex.tools.all_tags')" data-testid="cortex-tag-all" @click="choose(null)" />
          <UButton
            v-for="option in tags"
            :key="option"
            size="xs"
            color="neutral"
            :variant="tag === option ? 'solid' : 'outline'"
            :label="option"
            :data-testid="`cortex-tag-${option}`"
            @click="choose(option)"
          />
          <UInput v-model="search" class="ml-auto" icon="i-lucide-search" :placeholder="t('cortex.tools.search')" data-testid="cortex-tool-search" />
        </div>

        <UEmpty
          v-if="tools.length === 0"
          icon="i-lucide-wrench"
          :title="t('cortex.tools.empty')"
          variant="naked"
          role="status"
          data-testid="cortex-empty"
        />

        <UTable v-else :data="tools" :columns="columns" data-testid="cortex-tools">
          <template #name-cell="{ row }">
            <code class="text-xs" :data-testid="`cortex-tool-${row.original.name}`">{{ row.original.name }}</code>
          </template>
          <template #tags-cell="{ row }">
            <div class="flex flex-wrap gap-1">
              <UBadge v-for="toolTag in row.original.tags" :key="toolTag" color="neutral" variant="subtle" size="sm" :label="toolTag" />
            </div>
          </template>
          <template #actions-cell="{ row }">
            <UButton
              size="xs"
              variant="outline"
              icon="i-lucide-file-text"
              :to="`/cortex/tools/${row.original.name}/description`"
              :label="t('cortex.tools.edit_description')"
              :data-testid="`cortex-describe-${row.original.name}`"
            />
          </template>
        </UTable>
      </div>
    </CortexLoad>
  </CortexPage>
</template>
