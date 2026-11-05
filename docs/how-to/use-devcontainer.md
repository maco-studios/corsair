# Use a VS Code dev container

Generate a Dev Containers configuration during installation:

```bash
vendor/bin/corsair install --devcontainer
```

Corsair writes `.devcontainer/devcontainer.json`, points it at `compose.yaml`, selects the platform application service, and recommends the PHP Debug and Intelephense extensions. It also sets `WWWUSER` and `WWWGROUP` to `1000`.

Install the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers), open the project in VS Code, and choose **Reopen in Container**.

The flag only writes configuration; Docker and the extension are not required when the file is generated.

