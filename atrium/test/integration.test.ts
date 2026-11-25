import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import cortex, { screens } from '../src/atrium'
import { expectIntegrationManifest, missingI18nKeys } from '@refactor-circus/atrium/testing'
import { useRouter } from '@refactor-circus/atrium/testing'

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`
// into a served asset URL, which `fileURLToPath()` refuses.
const here = import.meta.url

/*
 * The Cortex integration as a whole (AC 49, 53, 55; D-090): the manifest
 * (`src/integration.json`) is valid as Atrium checks it, every screen it
 * declares has a layer page at its declared path, `src/atrium.ts` maps each to
 * that page's route, every hook tab and section it declares is registered in
 * the module's `extensions`, and every `t()` key the Cortex pages and
 * components use exists in `cortex.en.json`.
 */

const manifest = expectIntegrationManifest('src/integration.json', 'cortex')
const fixture = { ...manifest, screens: manifest.screens ?? [], search_sources: manifest.search_sources ?? [] }

/** Every tab and section key the manifest's hooks declare. */
function hookKeys(): string[] {
  return (manifest.hooks ?? []).flatMap(hook => [
    ...Object.keys((hook.tabs ?? {}) as Record<string, unknown>),
    ...Object.keys((hook.sections ?? {}) as Record<string, unknown>)
  ])
}

/** Every widget component key the manifest declares (`component`, else `key`). */
function widgetKeys(): string[] {
  return (manifest.widgets ?? []).map(widget => String(widget.component ?? widget.key))
}

/** Each screen's page file, under `src/pages/`. */
const PAGES: Record<string, string> = {
  'cortex.virtual-agents': 'virtual-agents/index.vue',
  'cortex.virtual-agent-create': 'virtual-agents/create.vue',
  'cortex.virtual-agent': 'virtual-agents/[slug].vue',
  'cortex.concrete-agents': 'concrete-agents/index.vue',
  'cortex.concrete-agent': 'concrete-agents/[agent].vue',
  'cortex.tools': 'tools/index.vue',
  'cortex.tool-description': 'tools/[tool]/description.vue',
  'cortex.servers': 'servers/index.vue',
  'cortex.server-instructions': 'servers/[server]/instructions.vue',
  'cortex.redirect-domains': 'redirect-domains.vue',
  'cortex.run': 'run.vue'
}

/** A route's path as declared in PHP: Nuxt's `:slug()` param form becomes `:slug`. */
function declared(path: string): string {
  return path.replace(/\(\)/g, '')
}

describe('the cortex integration', () => {
  it('declares the eleven v1 screens', () => {
    expect(fixture.screens.map(screen => screen.key)).toEqual([
      'cortex.virtual-agents',
      'cortex.virtual-agent-create',
      'cortex.virtual-agent',
      'cortex.concrete-agents',
      'cortex.concrete-agent',
      'cortex.tools',
      'cortex.tool-description',
      'cortex.servers',
      'cortex.server-instructions',
      'cortex.redirect-domains',
      'cortex.run'
    ])
  })

  it.each(fixture.screens.map(screen => [screen.key, screen.path] as const))('has a page for %s at %s, named in the screens map', (key, path) => {
    const name = screens[key]
    const route = useRouter().getRoutes().find(candidate => candidate.name === name)

    expect(name, key).toBeDefined()
    expect(route, `${key} -> ${name}`).toBeDefined()
    expect(declared(route!.path)).toBe(path)
  })

  it('declares a valid manifest for the cortex key', () => {
    expect(manifest.version).toBe(1)
    expect(manifest.key).toBe('cortex')
  })

  it('registers every hook tab and section the manifest declares', () => {
    expect(hookKeys().sort()).toEqual(['cortex.mcp', 'cortex.redirect-domains'])

    for (const key of hookKeys()) {
      expect(cortex.extensions?.[key], key).toBeDefined()
    }
  })

  it('registers every widget the manifest declares', () => {
    for (const key of widgetKeys()) {
      expect(cortex.widgets?.[key], key).toBeDefined()
    }
  })

  it('maps no screen the manifest does not declare', () => {
    expect(Object.keys(screens).sort()).toEqual(fixture.screens.map(screen => screen.key).sort())
  })

  it.each(Object.entries(PAGES))('frames the %s page with its own screen key, so it calls atrium.screens.show with it', (key, file) => {
    const source = readFileSync(fileURLToPath(new URL(`../src/pages/${file}`, here)), 'utf8')

    expect(source).toContain(`screen-key="${key}"`)
  })

  it('declares the cortex panel and search source with static data', () => {
    expect(fixture.settings?.key).toBe('cortex')
    expect(fixture.settings).not.toHaveProperty('data')
    expect(fixture.search_sources.map(source => source.key)).toEqual(['cortex'])
  })

  it('lists the old actions with no v2 route in a Not ported (D-032) comment', () => {
    const index = readFileSync(fileURLToPath(new URL('../src/atrium.ts', here)), 'utf8')

    expect(index.startsWith('// Not ported (D-032)')).toBe(true)
  })

  it('has every t() key it uses in its locale files', () => {
    expect(missingI18nKeys('cortex')).toEqual([])
  })
})
