<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import { cortexData, useCortexAction, useCortexLoad, type CortexList, type CortexVirtualAgent } from '../../components/cortex'
import CortexActionError from '../../components/CortexActionError.vue'
import CortexLoad from '../../components/CortexLoad.vue'
import CortexPage from '../../components/CortexPage.vue'

/**
 * `cortex.virtual-agents`: the virtual agents, a page at a time
 * (`virtual-agents.index?page=`), each with edit, run and delete.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCortexAction()

const page = computed(() => Math.max(1, Number(route.query.page) || 1))

const { client, data, pending, failed, status, reload } = useCortexLoad(async cortex =>
  cortexData<CortexList<CortexVirtualAgent>>(await cortex.GET('/virtual-agents', { params: { query: { page: page.value } } })))

watch(page, () => void reload())

const agents = computed(() => data.value?.data ?? [])
const lastPage = computed(() => data.value?.meta?.last_page ?? 1)

const columns = computed<TableColumn<CortexVirtualAgent>[]>(() => [
  { accessorKey: 'name', header: t('cortex.agent.name') },
  { accessorKey: 'slug', header: t('cortex.agent.slug') },
  { id: 'prompt', header: t('cortex.agent.prompt') },
  { id: 'provider', header: t('cortex.agent.provider') },
  { id: 'model', header: t('cortex.agent.model') },
  { id: 'actions', header: t('cortex.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

function goTo(target: number): void {
  void router.push({ query: { ...route.query, page: target > 1 ? String(target) : undefined } })
}

async function destroy(agent: CortexVirtualAgent): Promise<void> {
  if (!client || !window.confirm(t('cortex.virtual_agents.confirm_delete'))) {
    return
  }

  if (await action.run(async () => cortexData(await client.DELETE('/virtual-agents/{agent}', { params: { path: { agent: agent.slug } } })))) {
    await reload()
  }
}
</script>

<template>
  <CortexPage :title="t('cortex.virtual_agents.title')" screen-key="cortex.virtual-agents">
    <template #actions>
      <UButton to="/cortex/virtual-agents/create" icon="i-lucide-plus" :label="t('cortex.virtual_agents.new')" data-testid="cortex-new-agent" />
    </template>

    <CortexLoad :ready="!pending" :failed="failed" :status="status">
      <div class="flex flex-col gap-4">
        <CortexActionError :forbidden="action.forbidden.value" :message="action.message.value" />

        <UEmpty
          v-if="agents.length === 0"
          icon="i-lucide-bot"
          :title="t('cortex.virtual_agents.empty')"
          variant="naked"
          role="status"
          data-testid="cortex-empty"
        />

        <UTable v-else :data="agents" :columns="columns" data-testid="cortex-virtual-agents">
          <template #name-cell="{ row }">
            <ULink :to="`/cortex/virtual-agents/${row.original.slug}`" class="font-medium hover:underline" :data-testid="`cortex-agent-${row.original.slug}`">
              {{ row.original.name }}
            </ULink>
          </template>
          <template #slug-cell="{ row }">
            <code class="text-xs">{{ row.original.slug }}</code>
          </template>
          <template #prompt-cell="{ row }">
            {{ row.original.published_version ? `v${row.original.published_version}` : '—' }}
          </template>
          <template #provider-cell="{ row }">
            {{ row.original.provider ?? '—' }}
          </template>
          <template #model-cell="{ row }">
            {{ row.original.model ?? '—' }}
          </template>
          <template #actions-cell="{ row }">
            <div class="flex justify-end gap-2">
              <UButton
                size="xs"
                variant="outline"
                icon="i-lucide-pencil"
                :to="`/cortex/virtual-agents/${row.original.slug}`"
                :label="t('cortex.edit')"
                :data-testid="`cortex-edit-${row.original.slug}`"
              />
              <UButton
                size="xs"
                variant="outline"
                icon="i-lucide-play"
                :to="`/cortex/run?agent=virtual:${row.original.slug}`"
                :label="t('cortex.run.run')"
                :data-testid="`cortex-run-${row.original.slug}`"
              />
              <UButton
                size="xs"
                color="error"
                variant="outline"
                icon="i-lucide-trash"
                :label="t('cortex.delete')"
                :data-testid="`cortex-delete-${row.original.slug}`"
                @click="destroy(row.original)"
              />
            </div>
          </template>
        </UTable>

        <div v-if="lastPage > 1" class="flex items-center gap-2" data-testid="cortex-pages">
          <UButton size="xs" variant="outline" icon="i-lucide-chevron-left" :disabled="page <= 1" :aria-label="t('cortex.previous')" data-testid="cortex-previous" @click="goTo(page - 1)" />
          <span class="text-sm text-muted">{{ t('cortex.page', { page, last: lastPage }) }}</span>
          <UButton size="xs" variant="outline" icon="i-lucide-chevron-right" :disabled="page >= lastPage" :aria-label="t('cortex.next')" data-testid="cortex-next" @click="goTo(page + 1)" />
        </div>
      </div>
    </CortexLoad>
  </CortexPage>
</template>
