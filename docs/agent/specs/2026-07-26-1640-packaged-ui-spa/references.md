# References for Packaged UI SPA

## Package internals studied

### Service provider

- **Location:** `src/CortexServiceProvider.php`
- **Relevance:** UI route registration hooks into `boot()`; `registerMcpServers()`
  (lines 71-86) is the pattern for config-gated registration. `cortex-assets`
  publish tag (lines 55-57) already maps `public/ → public_path('vendor/cortex')`.
- **Key patterns:** config-gated private register method; publish tags grouped
  under umbrella `cortex` tag; console-only publish block.

### Config

- **Location:** `config/cortex.php`
- **Relevance:** `ui` block joins `routes` / `mcp` / `tools`; comment-block style
  with security notes to mirror.

### REST API surface

- **Location:** `routes/cortex.php`, `src/Http/Controllers/`, `src/Http/Requests/`,
  `src/Http/Resources/`, `src/Actions/`
- **Relevance:** The SPA consumes these 16 endpoints. Slug identifiers, JsonResource
  envelope (`{data}`, paginated `{data, links, meta}`), 201/204/404/422 semantics,
  validation rules in Actions' static `rules()` (agent settings keys, tool registry
  membership, sub-agent cycle checks).
- **Key patterns:** `Request::persist()` pass-through controllers; slugs public,
  IDs never exposed.

### Tests

- **Location:** `tests/Feature/Http/`, `tests/Feature/McpRegistrationTest.php`,
  `tests/Feature/ExampleTest.php`
- **Relevance:** Pest style for feature tests; McpRegistrationTest shows the
  re-boot pattern (and its limitation: cannot *remove* routes — disabled-state
  tests need pre-boot config via Testbench environment overrides).
  `ExampleTest.php:28` references `cortex::placeholder`, which the shell replaces.

### Workbench / build

- **Location:** `testbench.yaml`, `workbench/`, composer scripts
- **Relevance:** `asset-publish` build step ships package `public/` into the
  skeleton; `composer serve` for manual walkthrough.

## External pattern references

### Laravel Horizon

- **Relevance:** The packaging model — prebuilt SPA committed to package repo,
  published to `public/vendor/{package}`, Blade shell + `asset()` references,
  re-publish on upgrade (`--force` / composer `post-update-cmd`).
- **Key patterns:** stable asset names, config-gated dashboard route, middleware
  left to the consumer.
