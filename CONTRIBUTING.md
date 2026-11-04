# Contributing to Corsair

Thanks for considering a contribution. This project follows the
[Code of Conduct](CODE_OF_CONDUCT.md) — by participating, you're expected to uphold it.

## Before you start

- For a bug, check the [platform support matrix](docs/reference/platforms.md) first — a lot of
  reports turn out to be an unsupported PHP/database/search engine combination for a given
  Magento, OpenMage, Mage-OS, or MahoCommerce release.
- For a feature, open an issue to discuss the approach before writing code, especially if it
  touches the runtime images, the install flow, or how a platform is detected.
- Compatibility is project-specific: changes to `resources/runtimes/**` or `resources/stubs/**`
  need to be checked against every platform they affect, not just the one you're working on.

## Setup

```bash
git clone https://github.com/maco-studios/corsair.git
cd corsair
composer install
```

Requires PHP 8.2+, Composer, and Docker Engine with Compose (for the end-to-end suite).

## Running checks locally

```bash
composer test                                       # unit + feature suite (fast, no Docker)
vendor/bin/php-cs-fixer fix --dry-run --diff         # coding standards
vendor/bin/rector --dry-run                          # automated refactoring checks
composer test:e2e                                    # real Docker stacks — slow, needs Docker running
```

CI runs `composer test` and `php-cs-fixer` on every push and pull request, and the Docker-backed
end-to-end suite on a schedule, on release, and on pull requests that touch runtime, stub,
database, or console code. See [`.github/workflows/`](.github/workflows/) for the exact triggers.

## Coding standards

- `php-cs-fixer` and `rector` configs are committed (`.php-cs-fixer.php`, `rector.php`) — run
  them rather than hand-formatting.
- New PHP files need the project's MIT license header; `php-cs-fixer fix` adds it automatically.
- Keep platform-specific logic behind the existing platform-detection abstraction in
  `src/Console` rather than branching on platform name inline — see
  [platform detection](docs/explanation/platform-detection.md) for why.

## Commit messages

Project history uses [gitmoji](https://gitmoji.dev) + [Conventional Commits](https://www.conventionalcommits.org/),
e.g. `:bug: fix(console): handle missing compose.yaml`. This isn't required for your first PR —
a maintainer can adjust it on merge — but matching the convention makes review faster and keeps
`git log` useful as a map of the project's history.

## Submitting a pull request

- Keep PRs focused on one change; split unrelated fixes into separate PRs.
- Fill in the PR template — tests you ran, and whether docs needed updating.
- Never commit credentials or other secrets; `.env` is gitignored for this reason, and the
  Adobe Commerce Marketplace keys used by CI live in repository secrets, not in code.

## Documentation

User-facing changes should update the relevant page under [`docs/`](docs/README.md), which
follows the [Diátaxis](https://diataxis.fr/) framework (tutorials, how-to guides, reference,
explanation). Pick the section that matches what the reader is trying to do, not just where it
fits topically.
