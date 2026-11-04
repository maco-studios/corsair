# How PHP version detection works

`corsair:install` resolves which PHP runtime image to use, in this order:

1. **The `--php` option**, when it's set.
2. **`composer.json`**, by checking `config.platform.php` (an explicit platform override) and
   then `require.php` for a version constraint, and picking the newest of Corsair's supported
   runtimes (`8.0`–`8.5`) that satisfies it.
3. **The platform default**, if neither of the above applied, or if composer.json's constraint
   isn't satisfiable by any supported runtime: `8.5` for `maho`, `8.3` otherwise.

The result still has to pass the usual validation: it must be one of the supported runtimes, and
it must meet each platform's own minimum (`8.3` for `maho`, `8.1` for `openmage`).

## Why this exists

Each Magento/Maho/OpenMage/Mage-OS release pins its own minimum PHP version in its
`composer.json` — and that minimum moves with every major release. A project installed against
an older default (say, `8.3`) can end up on a Magento 2 release that actually requires `8.5`,
which surfaces as a confusing Composer platform-check failure (`Your Composer dependencies
require a PHP version ">= 8.5.0". You are running 8.3.36.`) rather than anything Corsair itself
reports. Reading the constraint straight from the project's own `composer.json` means the
runtime Corsair provisions actually matches what the project declares it needs, without the
developer having to track that mapping by hand or hit the error first.

## Why the newest satisfying version, not the oldest

Composer version constraints are almost always a lower bound with no upper bound (`>=8.5.0`), or
a short list of supported minor versions (`^8.1 || ^8.2`). Picking the newest supported runtime
that satisfies the constraint — rather than the oldest — means a project just gets the most
current, best-performing PHP it's compatible with, matching how most teams would choose manually.

## Why `config.platform.php` comes before `require.php`

`config.platform.php` is how a project tells Composer "pretend the environment is running this
exact PHP version," overriding whatever's actually installed — it's deliberately more specific
than the general `require.php` constraint, so Corsair honors the same override Composer itself
would.
