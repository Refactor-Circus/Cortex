import { atriumModule } from '@refactor-circus/atrium/vite'

// Cortex's Atrium module: `pnpm build` writes ../dist/atrium (committed), `pnpm test` runs Vitest.
export default atriumModule({
  key: 'cortex',
  outDir: '../dist/atrium',
  inputs: ['src', '../client/src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml'],
  test: { setupFiles: ['test/setup.ts'] },
})
