# Compatibility

Corsair currently supports PHP runtime images from 8.0 through 8.5. The selected runtime must satisfy the application's effective Composer platform requirements.

Platform-specific enforcement:

- OpenMage requires PHP 8.1 or later.
- Maho requires PHP 8.3 or later.
- Maho 26.11 requires PHP 8.5 or later; older supported PHP versions receive a warning for Maho 26.9.

The PHP version is selected from `config.platform.php`, the root `require.php` constraint, and locked package PHP constraints. You can override it with `--php=<version>` when the chosen runtime is compatible with the project.

Platform release requirements, authentication, database versions, search compatibility, and application upgrades belong to the platform's official documentation.

