# Contribution Guide

Thank you for considering contributing to Cortex! Please review the following guidelines before submitting a pull request.

For significant changes, please open an issue first so we can discuss the approach.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit, and push
4. Open a pull request detailing your changes

## Guidelines

- Ensure the coding style passes by running `composer lint`.
- Send a coherent commit history, making sure each commit in your pull request is meaningful.
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
- Please remember that we follow [SemVer](http://semver.org/).

## Setup

Clone your fork, then install the dev dependencies:

```bash
composer install
```

## OpenAPI document and TypeScript client

Cortex's PHP ships no UI. Its Atrium v2 front-end module lives in `atrium/` (Vue, built on the JSON API): after changing it run `pnpm -C atrium install`, `pnpm -C atrium typecheck`, `pnpm -C atrium test` and `pnpm -C atrium build`, and commit `dist/atrium/` (a test fails while the build is stale). The API is described by `openapi.json` at the package root, and `client/` holds the typed TypeScript client generated from it, `@refactor-circus/cortex-client`. When you add or change a route, regenerate both (this needs Node and npm):

```bash
composer client:generate
```

It exports `openapi.json` from the workbench, installs the client's packages, regenerates `client/src/schema.d.ts` and type-checks the client. Commit `openapi.json` and `client/src/schema.d.ts` together: the tests fail while either is stale. Try the API in the workbench with `composer serve`.

## Lint

Lint your code:

```bash
composer lint
```

## Tests

Run all tests:

```bash
composer test
```
