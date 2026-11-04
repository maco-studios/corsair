## What does this change?

<!-- One or two sentences on what changed and why. -->

## Related issue

<!-- Closes #123, or "N/A" if there isn't one. -->

## How was this tested?

- [ ] `composer test` passes locally (unit + feature suite)
- [ ] `vendor/bin/php-cs-fixer fix --dry-run --diff` is clean
- [ ] `vendor/bin/rector --dry-run` is clean (if touching `src/`)
- [ ] `composer test:e2e` was run locally, if this touches `resources/runtimes`, `resources/stubs`, `resources/database`, `src/`, or `bin/corsair`

## Checklist

- [ ] I did not include credentials, tokens, or other secrets in this PR
- [ ] Docs under `docs/` were updated, if this changes user-facing behavior
