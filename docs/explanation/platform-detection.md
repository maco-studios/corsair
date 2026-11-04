# How platform detection works

Every command that needs to know whether it's working with Magento, Maho, OpenMage, or Mage-OS resolves it the same way, in this order:

1. **The `--platform` option**, when the command accepts one and it's set (only `install` takes this option — `add`, `maho`, and `maho install` always infer it).
2. **The existing `compose.yaml`**, if one exists:
   - first, by reading the `CORSAIR_PLATFORM` environment variable off any service in the file (this is the value `install` wrote the first time);
   - failing that, by checking whether a `maho.test` service exists, in which case the platform is `maho`.
3. **`composer.json`**, by looking at `require` and `require-dev` for a package starting with `mahocommerce/`, `openmage/`, `mage-os/`, or `magento/`, in that order. The first match wins, so a project that depends on both a Maho and a Magento package (unusual, but possible during a migration) resolves to `maho`.
4. **An interactive prompt**, defaulting to `magento`, if the terminal is interactive.
5. **`magento`**, as the final fallback, if none of the above applied and the terminal isn't interactive.

The result is cached for the lifetime of the command (`resolvedPlatform`), so a single invocation never asks or re-detects twice, even if it calls `platform()` from several places (building `compose.yaml`, writing `.env`, choosing the default PHP version, and so on).

## Why detection from `composer.json` comes before prompting

Corsair is installed as a `require-dev` dependency of the Magento/Maho/OpenMage/Mage-OS project itself, not as a standalone tool pointed at an external directory. That means the project's own `composer.json` is already sitting right next to `vendor/bin/corsair` and reliably names the platform package — there's no reason to ask the developer something the project file already answers. The prompt only exists as a fallback for projects that don't follow the normal dependency naming (private forks, mirrors, etc.) or that have no platform package listed yet.

## Why `compose.yaml` is checked before `composer.json`

Once a project has been installed with `install`, `compose.yaml` is the more authoritative source: it's possible (if unusual) to install Corsair for a platform other than the one the dev dependency's name would imply, and subsequent commands (`add`, `maho`) must keep agreeing with whatever was actually installed, not re-derive a possibly different answer from `composer.json` each time.
