# Standards for Register Roster redirect domains

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Atrium moves to suggest; the integration is config-only and guarded by class_exists() |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | New `cortex.atrium.redirect_domains.screens` key with a banner and safe defaults |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | New tab and section keys, screen keys and owner fields on the UI routes |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Screen registration from boot(), behind the Atrium guard |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | New `Atrium\ScreenRegistrations` and `Atrium\Screens` classes and screen keys |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Visibility and writes go through RedirectDomainPolicy with the owner; the screens' access() closures use the Gate |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | The existing UI store and destroy routes take optional owner fields; no new routes |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Owners are named by morph type and key, resolved by RedirectDomains::owner() |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New arch rule: only src/Atrium uses Atrium |
| testing/test-layers | docs/agent/standards/testing/test-layers.md | local (overrides refactor-circus/standards@dev-main) | Owned-domain behaviour stays tested at the actions; the UI tests cover wiring through a workbench test host |
| cortex/atrium-screens | docs/agent/standards/cortex/atrium-screens.md | local | x-atrium components only; its History section is replaced by screen declaration |
| cortex/optional-surfaces | docs/agent/standards/cortex/optional-surfaces.md | local | Registration follows `cortex.ui.enabled` |
| cortex/testbench-environment | docs/agent/standards/cortex/testbench-environment.md | local | The test case adds the test-host routes and workbench migrations, plus a WithoutAtrium suite |
| cortex/domains | docs/agent/standards/cortex/domains.md | local | RedirectDomain domain actions and service are reused unchanged |
