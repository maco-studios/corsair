# How to add a service to an existing installation

Use `add` to add one or more of the [supported services](../reference/services.md) after the initial install, without changing the platform or PHP version:

```bash
vendor/bin/corsair add mailpit
vendor/bin/corsair add rabbitmq,varnish
```

With no argument, it prompts interactively for which services to add (in a non-interactive shell, it falls back to the platform's default service set). Pass `none` to add nothing — useful for scripting a bare install followed by selective `add` calls.

`add` merges the new services into `compose.yaml` (adding them to the app container's and the scheduler/queue containers' `depends_on`) and writes their corresponding keys into `.env`. It then pulls and builds the new services' images, the same as `install` does.

Bring the new service up:

```bash
vendor/bin/corsair up -d
```

Running `add` again with a service that's already installed is safe — Corsair skips services that already exist in `compose.yaml` rather than duplicating or resetting them.
