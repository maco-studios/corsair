# Customization model

Corsair starts with package-owned runtime files so installation is simple and upgrades can bring in improvements. When a project needs a local runtime change, `publish` copies those files into `./docker` and rewrites the Compose build paths.

After publishing, the project owns the copied Docker files. This makes changes reviewable and reproducible, but future Corsair runtime changes must be adopted intentionally. Commit the published directory when the project depends on it.

