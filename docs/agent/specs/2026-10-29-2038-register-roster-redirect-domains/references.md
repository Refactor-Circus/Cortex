# References

- Roster's current implementation: `Roster/src/Atrium/RedirectDomains.php`, `Roster/src/Atrium/Http/Controllers/RedirectDomainUiController.php`, `Roster/resources/views/ui/partials/redirect-domains.blade.php`, the routes at `Roster/src/Atrium/RosterPlugin.php:149-150, :174-175`, and `Roster/tests/Cortex/RedirectDomainsTest.php`.
- Where it renders today: `OrganizationUiController::offers()` (:310, the `mcp` tab) and `UserUiController:328` plus `users/partials/overview.blade.php:249`.
- Cortex domain: `Cortex/src/Domains/RedirectDomain/` (actions, `Services/RedirectDomains.php`, policy).
- Cortex inline trails: `Cortex/resources/views/ui/virtual-agents/index.blade.php:77`, `form.blade.php:150` and `ui/partials/override.blade.php:60`.
