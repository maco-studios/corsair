# Changelog

<a name="0.0.1"></a>
## 0.0.1 (2026-11-04)

### Added

- ✨ feat(readme): add buy me a coffee link [[4363abd](https://github.com/maco-studios/corsair/commit/4363abd62392dba43d3454fe5b8d98065529735f)]
- ✅ test(console): cover platform-detection edge cases [[52beb5c](https://github.com/maco-studios/corsair/commit/52beb5c70e854d73c560f1261c9ba9cd455527a5)]
- ✅ test(console): cover remaining trait branches [[0bf2341](https://github.com/maco-studios/corsair/commit/0bf23419437a6d5244aac053305e3b5618b2fa5e)]
- ✅ test(console): cover the real-docker and error paths [[d60cbc6](https://github.com/maco-studios/corsair/commit/d60cbc69efdd14fe58144125d705d47b388498a3)]
- 👷‍♂️ ci(github): add dependency review and stale bot [[134734d](https://github.com/maco-studios/corsair/commit/134734d6d568f25e196d9fb77e6f513f3298c466)]
- 👷‍♂️ ci(release): gate publishing on tests and e2e [[3adcd58](https://github.com/maco-studios/corsair/commit/3adcd5888764500b71122fc97669dc8556afbea4)]
- 👷‍♂️ ci(e2e): run the e2e suite on published releases [[4f4581f](https://github.com/maco-studios/corsair/commit/4f4581fba5ba7d2c4ad163165f9166337f37467f)]
- ✨ feat(ci): add test coverage reporting and a static badge [[e320801](https://github.com/maco-studios/corsair/commit/e32080143593544d3590d1a5a864706824ea4007)]
- 👷‍♂️ ci(github): add unit and real-docker e2e workflows [[f203433](https://github.com/maco-studios/corsair/commit/f203433b3ec77417c547f1ca25c72465741f3f29)]
- ✅ test(e2e): add real-project suite for all platforms [[ef5ff5c](https://github.com/maco-studios/corsair/commit/ef5ff5c27e27bc1ce090544681528120582b4ea8)]
- ✨ feat(console): add sail-style command forwarding to corsair [[fcff5f0](https://github.com/maco-studios/corsair/commit/fcff5f005897e63369502b42eb7422c000217902)]
- ✨ feat(assets): split the platform badge per platform [[5f5553c](https://github.com/maco-studios/corsair/commit/5f5553cf68c0a1b8cca03e46c06c1994a4f7db8b)]
- ✅ test(runtimes): cover scheduler/queue services [[8a75301](https://github.com/maco-studios/corsair/commit/8a75301fc3624dd3ee9e52e1ccdcb9a36266cd43)]
- ✨ feat(runtimes): run cron/queue in own containers [[98c1864](https://github.com/maco-studios/corsair/commit/98c186423abedf074cb28b7398b575951af83ca5)]
- 🔊 chore(runtimes): log php, nginx, supervisord to stdout [[42dda08](https://github.com/maco-studios/corsair/commit/42dda081ff542d70705b86d244a059815a7f7ecd)]
- ✅ test(console): add unit tests for platform detection [[6d33a19](https://github.com/maco-studios/corsair/commit/6d33a19e4a672d56116aac245dc128670d20f2c3)]
- ✨ feat(console): add Maho, OpenMage, and Mage-OS support [[abb7c8c](https://github.com/maco-studios/corsair/commit/abb7c8cf8caef235eedb5dcd4afcd708992157c3)]
- ✅ test(console): cover CLI commands and temp helper [[f2ec8bd](https://github.com/maco-studios/corsair/commit/f2ec8bdd49a3023077f3e5c7b6a1be52ba715a10)]
- ✨ feat(console): add install, add, and publish CLI commands [[0a50d3a](https://github.com/maco-studios/corsair/commit/0a50d3ae3e16449b6c26a67601e4e8ad9d48818c)]
- ✨ feat(stubs): add compose and docker service stubs [[581895d](https://github.com/maco-studios/corsair/commit/581895d7c0c02b02c620701e26405c17f8e21c50)]
- ✨ feat(runtimes): add PHP 8.0-8.5 Docker runtime images [[d6a6cf8](https://github.com/maco-studios/corsair/commit/d6a6cf8a8f701de51e6e71031f5e94e744a60897)]
- ➕ build: add symfony console, process, and yaml deps [[3eeb18b](https://github.com/maco-studios/corsair/commit/3eeb18b96c95911071accd8d0060fcbe8d5f6ab2)]
- ✅ test: scaffold pest test suite [[183ebc9](https://github.com/maco-studios/corsair/commit/183ebc99a4708b040899cffc369413ecb997bf3d)]
- 🎉 chore: begin the corsair project [[4b2f346](https://github.com/maco-studios/corsair/commit/4b2f3462802b4903892968e357f80d178f0b634d)]

### Changed

- ⏪ revert(release): drop the auto-publish gate workflow [[3366f62](https://github.com/maco-studios/corsair/commit/3366f62ad9e33803df057e91943907294d4c945d)]
- ⬆️ build(deps): widen symfony constraints for ecosystem compat [[98deefa](https://github.com/maco-studios/corsair/commit/98deefab36736cdfae618afab8f8ca4dab439e95)]
- 🚚 refactor(assets): rename magento badge to platform-magento [[1ded3fb](https://github.com/maco-studios/corsair/commit/1ded3fb3ed0b44c74e243265a7cc08616029a408)]
- 🗃️ feat(database): add mysql mariadb and pgsql init scripts [[e30bcd1](https://github.com/maco-studios/corsair/commit/e30bcd150b5a55e71f7f9d0eaa3bba2bd370d3f0)]
- 💬 fix: update repository urls from vessel to corsair [[e431ed9](https://github.com/maco-studios/corsair/commit/e431ed929a729f5e1e59ac001c90ad122d42cccd)]
- 🍱 chore(badges): replace product badges with unified magento badge [[7ed1ee5](https://github.com/maco-studios/corsair/commit/7ed1ee529eb771220d960ebf4c69b15c99ead64c)]
- 🍱 chore: add logo and readme badge assets [[b4b1284](https://github.com/maco-studios/corsair/commit/b4b12841e35993cde09692115511fbf54ee687f3)]
- 🔧 chore: add php-cs-fixer and rector configs [[9e19ed1](https://github.com/maco-studios/corsair/commit/9e19ed1b81231cff89e74b311490e4e2c477e825)]

### Removed

- 🔥 chore(tests): remove placeholder example tests [[92c3bae](https://github.com/maco-studios/corsair/commit/92c3bae71b2bfb3cfca950832b2f7ea1c73cd286)]

### Fixed

- 🐛 fix(console): dedupe named volumes by their written key [[42d788d](https://github.com/maco-studios/corsair/commit/42d788d40ef938a0876f1c5846512c1cc1c88104)]
- 🐛 fix(ci): add missing license header to coverage badge script [[4a49384](https://github.com/maco-studios/corsair/commit/4a49384b93e436e0926763d0ef0e9ee9e8108008)]
- 🐛 fix(ci): trigger tests workflow on main instead of master [[5adae36](https://github.com/maco-studios/corsair/commit/5adae362ed5d66f0bcb737f0e5cd30ca293110b8)]
- 💚 fix(ci): bump host php to 8.4 so composer install works [[3686c24](https://github.com/maco-studios/corsair/commit/3686c241e34a49c52eed8cb90f45dba578bf22d4)]
- 🐛 fix(mysql): disable binary logging in the mysql stub [[95901db](https://github.com/maco-studios/corsair/commit/95901dbfdaace125bf0a4dbc8b7615d52898acc3)]
- 🐛 fix(docker): run container execs as the web server&#x27;s uid [[5648c94](https://github.com/maco-studios/corsair/commit/5648c94b50f346d265f0622cc6b933fbc184028e)]
- 🐛 fix(nginx): quote the maho cache-path regex to stop a crash loop [[4c689e4](https://github.com/maco-studios/corsair/commit/4c689e4936e53fdf7e4c1bef15664ab61d0f065a)]
- 🐛 fix(console): implement the missing Application class [[1150e30](https://github.com/maco-studios/corsair/commit/1150e30be9ef53affccd687e0eb238f5c6342fea)]

### Miscellaneous

- 📊 chore(ci): update coverage badge [[57521cc](https://github.com/maco-studios/corsair/commit/57521cc163a5a3d2f12609435b64b5a4acecb461)]
- 📊 chore(ci): update coverage badge [[2cd780d](https://github.com/maco-studios/corsair/commit/2cd780de36bc866b9d175cda0e53cbc5fbc08553)]
- 📊 chore(ci): update coverage badge [[d858742](https://github.com/maco-studios/corsair/commit/d8587428e976b86fddb445c2126e0d282d093386)]
- 📊 chore(ci): update coverage badge [[b5c52ff](https://github.com/maco-studios/corsair/commit/b5c52fff8598e0f6602eeb5f664089a437638a85)]
- 📊 chore(ci): update coverage badge [[7576871](https://github.com/maco-studios/corsair/commit/7576871c1dc2f07b941d749975bab9427c418034)]
- 🚧 chore(readme): mark the project as under construction [[228dccd](https://github.com/maco-studios/corsair/commit/228dccdf9f9538ddc428e8c26a4cc148bcb300cb)]
- 📝 docs(readme): trim the badge row down to 3 [[8925893](https://github.com/maco-studios/corsair/commit/8925893fdbb71a485bb3b6aef1899165b0e1074e)]
- 📝 docs: add packagist metadata and version badges [[6c12153](https://github.com/maco-studios/corsair/commit/6c121539ce1f7675b478156675e46136fb3aa7d6)]
- 📝 docs(github): add issue and pull request templates [[6515af3](https://github.com/maco-studios/corsair/commit/6515af30f3c2fc0f70dfa396aca7fea4757a216c)]
- 📝 docs: add contributing guide [[0b53411](https://github.com/maco-studios/corsair/commit/0b53411fd00562e1f346d9d7a50ceb0099232a24)]
- 📝 docs: add contributor covenant code of conduct [[30c816e](https://github.com/maco-studios/corsair/commit/30c816e432fa766f94f0490857458f00987a20af)]
- 📝 docs(changelog): add CHANGELOG.md for the 0.0.1 release [[ce61cf2](https://github.com/maco-studios/corsair/commit/ce61cf2b3d2d73c2fef60f53bf4e362e186ccc44)]
- 📝 docs: tighten readme intro and quickstart copy [[3f7c945](https://github.com/maco-studios/corsair/commit/3f7c945ad0353c3de4f7f477badc029b7ad77369)]
- 🙈 chore(git): ignore the local .env file [[d373e3d](https://github.com/maco-studios/corsair/commit/d373e3d254bcc99938977b95848dc050edc9643d)]
- 🩹 fix(openmage): patch install.php cache-lock crash [[10c6806](https://github.com/maco-studios/corsair/commit/10c6806a1d7f452d1b173173b335e77435b649a7)]
- 📝 docs: rewrite docs with the diataxis framework [[4fa2bef](https://github.com/maco-studios/corsair/commit/4fa2bef5a9760ef82ad38bf52d76b1d46980ce22)]
- 🙈 chore: ignore var workspace except tmp gitkeep [[b038b17](https://github.com/maco-studios/corsair/commit/b038b171dccabf8bc6688f8acf159cea1f66e979)]
- 📝 docs(readme): point badge row at unified magento asset [[72fe3bc](https://github.com/maco-studios/corsair/commit/72fe3bccd6c512eda9e2d90d97e98085fb0f8c52)]
- 📝 docs: add project readme with logo and badges [[5ef5ad5](https://github.com/maco-studios/corsair/commit/5ef5ad52ac69cb67439c519b699b0dd19197c903)]
- 📄 docs: add mit license [[600ea7b](https://github.com/maco-studios/corsair/commit/600ea7b489f81fed503f4b5db4306a9e23084625)]
- 🙈 chore: add gitignore for vendor and tool caches [[93d0a97](https://github.com/maco-studios/corsair/commit/93d0a97eb580e806d66e9b2d927631572527bcfe)]


