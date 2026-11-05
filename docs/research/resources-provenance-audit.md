# Resources provenance audit

Date: 2026-11-05

This is a source-code similarity audit, not a legal opinion. The measurements below were taken before adding the attribution headers now present in the seven directly sourced files. “Direct copy” means byte-for-byte identical to the checked upstream snapshot before those headers were added. “Line similarity” is the longest common subsequence of non-blank lines after removing line endings; it is useful for locating shared implementation, but generic Docker and shell idioms can produce similarity without copying.

## Sources checked

- `laravel/sail` checkout: commit `56c1c94117ef12ee707d50433273228ebee7138e`, branch `1.x`.
- `markshust/docker-magento` checkout: commit `c06c98ed6fe7f8b86538ac5e72c24b29f0a71db0`, branch `release/next`.
- Local resources: `resources/runtimes` and `resources/database` at the working-tree revision.

The upstream repositories are relevant but have different scopes. Sail’s README describes Sail as Docker files for Laravel and says it was inspired by and derived from Vessel; it also identifies the project as MIT licensed ([Sail README](https://github.com/laravel/sail#readme)). The docker-magento PHP image source explicitly shows its PHP 8.3 image structure, `msmtprc`, PHP configuration, FPM configuration, and Xdebug copy points ([PHP 8.3 Dockerfile](https://raw.githubusercontent.com/markshust/docker-magento/release/next/images/php/8.3/Dockerfile), [PHP 8.3 Xdebug config](https://raw.githubusercontent.com/markshust/docker-magento/release/next/images/php/8.3/conf/php-xdebug.ini)).

## Results

### Direct byte-for-byte matches

There are 69 files under the two local resource trees. Before the attribution headers were added, seven were exact matches:

| Local files | Upstream | Exact matches |
| --- | --- | ---: |
| `resources/database/mariadb/create-testing-database.sh` | Sail `database/mariadb/create-testing-database.sh` | 1 |
| `resources/database/mysql/create-testing-database.sh` | Sail `database/mysql/create-testing-database.sh` | 1 |
| `resources/database/pgsql/create-testing-database.sql` | Sail `database/pgsql/create-testing-database.sql` | 1 |
| `resources/runtimes/8.2–8.5/conf/php-xdebug.ini` | docker-magento `images/php/8.2–8.5/conf/php-xdebug.ini` | 4 |

That is **7/69 files = 10.1% of files**. By non-blank local lines, those exact files account for **44/3,149 = 1.4%**. The line percentage is low because the local nginx/platform configuration is much larger than the exact database and Xdebug snippets.

The database result is unambiguous: each local database initializer is identical to the corresponding Sail file. For example, Sail’s MySQL initializer contains the same database creation and grant statements ([source](https://raw.githubusercontent.com/laravel/sail/1.x/database/mysql/create-testing-database.sh)); the MariaDB and PostgreSQL counterparts are also directly available ([MariaDB](https://raw.githubusercontent.com/laravel/sail/1.x/database/mariadb/create-testing-database.sh), [PostgreSQL](https://raw.githubusercontent.com/laravel/sail/1.x/database/pgsql/create-testing-database.sql)).

### Shared implementation by family

For files that have a meaningful counterpart in one of the two upstream trees, the best normalized line match was **541/1,187 non-blank lines = 45.6%**, across 43 files. This is not the percentage of the whole resource tree: 26 local files, principally nginx and platform-specific configuration, have no counterpart under the two URLs and were excluded from that pairwise comparison.

Including those unmatched local files as zero similarity gives **541/3,149 = 17.2%** line-level overlap across the complete local resource tree.

The strongest non-exact family is the PHP Dockerfile:

- Local `resources/runtimes/8.2–8.5/Dockerfile` vs docker-magento PHP images: approximately **65.4–65.7%** common ordered non-blank lines per version.
- The shared portions include the `php:<version>-fpm-bookworm` base image, NodeSource setup, Debian package installation, PHP extension installation, Composer installation, and PHP configuration copy pattern.
- The local files materially diverge in maintainer identity, user/group model (`corsair` versus `app`), runtime services, extensions, logging, nginx integration, scheduler/queue behavior, and Corsair platform support. The docker-magento source itself shows the upstream `app` user and package/extension layout ([PHP 8.3 Dockerfile](https://raw.githubusercontent.com/markshust/docker-magento/release/next/images/php/8.3/Dockerfile)).

The lower-level configuration has a mixed result:

- `php-xdebug.ini` for 8.2–8.5: **100% exact** docker-magento matches.
- `php.ini`: approximately **69.2%** ordered-line overlap with docker-magento for 8.2–8.5, but not byte-identical because Corsair adds its own limits, logging, timezone, sendmail, and input settings.
- `php-fpm.conf`: approximately **75%** ordered-line overlap with docker-magento.
- `msmtprc`: approximately **40%** ordered-line overlap with docker-magento.
- `www.conf`: approximately **12.5%** ordered-line overlap with docker-magento.

The current Sail runtime files are a poor byte-level baseline for the local runtimes because Sail has since moved to a different Ubuntu-based image and a different `sail` user/service model. The local `start-container` still shares the recognizable Sail-style user remapping, Composer directory setup, and command-versus-Supervisord dispatch shape, but it adds Corsair-specific ownership, nginx, Magento/Maho/OpenMage, and scheduler behavior. The current Sail README confirms the project’s Vessel lineage and Docker-file scope ([README](https://github.com/laravel/sail#readme)).

## Interpretation

The defensible summary is:

1. **Database resources:** direct copies from Laravel Sail in the checked snapshot: 3/3 files, 100% exact.
2. **Xdebug configuration:** direct copies from docker-magento for the versions where both projects publish the file: 4/4 files, 100% exact.
3. **PHP runtime Dockerfiles:** substantial structural/reference overlap with docker-magento, around 65% ordered-line similarity, with significant Corsair-specific additions and changes.
4. **Runtime startup/Supervisor files:** shared conventions and ancestry with Sail, but not current byte-for-byte copies.
5. **Nginx and platform files:** no direct counterpart in the two requested source paths; these appear to be Corsair-specific from this comparison alone.

The percentages should not be presented as “percent plagiarism” or as an authorship determination. They quantify textual overlap against two particular revisions. A complete provenance review would additionally inspect commit history, older upstream revisions from the dates when the local files were introduced, license notices, and any other source projects used during development.
