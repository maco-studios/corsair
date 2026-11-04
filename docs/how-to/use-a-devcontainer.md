# How to use a VS Code dev container

Pass `--devcontainer` to `install` to generate a `.devcontainer/devcontainer.json` alongside `compose.yaml`:

```bash
vendor/bin/corsair install --devcontainer
```

This writes a dev container configuration that:

- points `dockerComposeFile` at your project's `compose.yaml`;
- uses the app service (`magento.test`, or `maho.test` for `--platform=maho`) as the dev container's `service`;
- sets the workspace folder to `/var/www/html`;
- installs the `xdebug.php-debug` and `bmewburn.vscode-intelephense-client` VS Code extensions automatically.

It also sets `WWWUSER=1000` and `WWWGROUP=1000` in `.env`, so files created inside the container are owned by a UID/GID that matches a typical Linux host user and don't end up needing `sudo chown` to edit from outside the container.

Open the project folder in VS Code afterward and choose **Reopen in Container** — no further Corsair commands are needed.

`--devcontainer` only writes the file; it doesn't require Docker or the Dev Containers extension to be present at install time, but you'll need the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) installed to use it.
