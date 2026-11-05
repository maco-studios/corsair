# PHP version detection

When `--php` is omitted, Corsair chooses the newest bundled runtime that satisfies the project's effective Composer PHP constraints.

An explicit `config.platform.php` value wins. Otherwise Corsair considers the root `require.php` constraint and PHP constraints declared by locked packages in `composer.lock`. If no usable constraint exists, it uses PHP 8.5 for Maho and PHP 8.3 for other platforms.

The purpose is to prevent the container runtime from being newer than the platform Composer check allows. Use `--php` only when you have confirmed the selected runtime satisfies the project and its dependencies.
