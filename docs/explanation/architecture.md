# Architecture

An installation has one application container and optional service containers. The project directory is mounted into the application container at `/var/www/html`, while stateful services use named Docker volumes.

The application, scheduler, and queue containers share the selected PHP runtime. Nginx serves the platform's expected document root and PHP-FPM executes application requests.

The generated Compose file records the topology; `.env` supplies project-specific ports, credentials, and runtime switches.

