<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import { cortexData, useCortexLoad, type CortexConcreteAgent, type CortexList } from '../../components/cortex'
import CortexLoad from '../../components/CortexLoad.vue'
import CortexPage from '../../components/CortexPage.vue'

/**
 * `cortex.concrete-agents`: the agents declared in code
 * (`concrete-agents.index`), with whether their prompt and tools are
 * overridden.
 */
const { t } = useI18n()

const { data, pending, failed, status } = useCortexLoad(async cortex =>
  cortexData<CortexList<CortexConcreteAgent>>(await cortex.GET('/concrete-agents')).data)

const agents = computed(() => data.value ?? [])

const columns = computed<TableColumn<CortexConcreteAgent>[]>(() => [
  { accessorKey: 'name', header: t('cortex.agent.name') },
  { id: 'prompt', header: t('cortex.agent.prompt') },
  { id: 'tools', header: t('cortex.agent.tools') },
  { id: 'actions', header: t('cortex.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])
</script>

<template>
  <CortexPage :title="t('cortex.concrete_agents.title')" screen-key="cortex.concrete-agents" :description="t('cortex.concrete_agents.description')">
    <CortexLoad :ready="!pending" :failed="failed" :status="status">
      <UEmpty
        v-if="agents.length === 0"
        icon="i-lucide-bot"
        :title="t('cortex.concrete_agents.empty')"
        variant="naked"
        role="status"
        data-testid="cortex-empty"
      />

      <UTable v-else :data="agents" :columns="columns" data-testid="cortex-concrete-agents">
        <template #name-cell="{ row }">
          <ULink :to="`/cortex/concrete-agents/${row.original.name}`" class="font-medium hover:underline" :data-testid="`cortex-agent-${row.original.name}`">
            {{ row.original.name }}
          </ULink>
        </template>
        <template #prompt-cell="{ row }">
          <UBadge
            v-if="row.original.published_version !== null"
            color="success"
            variant="subtle"
            :label="t('cortex.versions.overridden', { version: row.original.published_version })"
            data-status="overridden"
          />
          <UBadge v-else color="neutral" variant="subtle" :label="t('cortex.versions.from_code')" data-status="from_code" />
        </template>
        <template #tools-cell="{ row }">
          <UBadge v-if="row.original.tools_overridden" color="success" variant="subtle" :label="t('cortex.tools.overridden')" />
          <span v-else class="text-muted">{{ t('cortex.tools.count', { count: row.original.tools.length }) }}</span>
        </template>
        <template #actions-cell="{ row }">
          <div class="flex justify-end gap-2">
            <UButton size="xs" variant="outline" icon="i-lucide-pencil" :to="`/cortex/concrete-agents/${row.original.name}`" :label="t('cortex.edit')" />
            <UButton
              size="xs"
              variant="outline"
              icon="i-lucide-play"
              :to="`/cortex/run?agent=concrete:${row.original.name}`"
              :label="t('cortex.run.run')"
              :data-testid="`cortex-run-${row.original.name}`"
            />
          </div>
        </template>
      </UTable>
    </CortexLoad>
  </CortexPage>
</template>
