# How to add a service to an existing installation

Use `corsair:add` to add one or more of the [supported services](../reference/services.md) after the initial install, without changing the platform or PHP version:

```bash
vendor/bin/corsair corsair:add mailpit
vendor/bin/corsair corsair:add rabbitmq,varnish
```

With no argument, it prompts interactively for which services to add (in a non-interactive shell, it falls back to the platform's default service set). Pass `none` to add nothing — useful for scripting a bare install followed by selective `corsair:add` calls.

`corsair:add` merges the new services into `compose.yaml` (adding them to the app container's and the scheduler/queue containers' `depends_on`) and writes their corresponding keys into `.env`. It then pulls and builds the new services' images, the same as `corsair:install` does.

Bring the new service up:

```bash
vendor/bin/corsair up -d
```

Running `corsair:add` again with a service that's already installed is safe — Corsair skips services that already exist in `compose.yaml` rather than duplicating or resetting them.
