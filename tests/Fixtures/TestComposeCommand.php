<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

namespace Tests\Fixtures;

use Corsair\Console\Command;
use Corsair\Console\Concerns\InteractsWithDockerComposeServices;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exposes the protected members of InteractsWithDockerComposeServices so they
 * can be exercised directly, without going through a full command execution.
 */
final class TestComposeCommand extends Command
{
    use InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->setName('test:compose')
            ->addOption('platform', null, InputOption::VALUE_REQUIRED)
            ->addOption('php', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function withInput(array $options = [], bool $interactive = false): static
    {
        $input = new ArrayInput($options, $this->getDefinition());
        $input->setInteractive($interactive);

        $this->initialize($input, new NullOutput());

        return $this;
    }

    public function callPlatform(): string
    {
        return $this->platform();
    }

    public function callPlatformFromComposerJson(): ?string
    {
        return $this->platformFromComposerJson();
    }

    /**
     * @return list<string>
     */
    public function callDefaultServices(): array
    {
        return $this->defaultServices();
    }

    public function callPhpVersion(): string
    {
        return $this->phpVersion();
    }

    public function callApplicationServiceName(): string
    {
        return $this->applicationServiceName();
    }

    /**
     * @param  array<string, mixed>  $compose
     */
    public function callApplicationServiceFromCompose(array $compose): ?string
    {
        return $this->applicationServiceFromCompose($compose);
    }

    public function callSetEnvValue(string $environment, string $key, string $value): string
    {
        return $this->setEnvValue($environment, $key, $value);
    }

    public function callDefaultEnvContents(): string
    {
        return $this->defaultEnvContents();
    }

    public function callEnvironmentValue(mixed $environment, string $key): ?string
    {
        return $this->environmentValue($environment, $key);
    }

    public function callComposePath(): string
    {
        return $this->composePath();
    }

    public function callStubPath(string $stub): string
    {
        return $this->stubPath($stub);
    }

    public function callPackagePath(string $path = ''): string
    {
        return $this->packagePath($path);
    }

    public function callStackHasService(string $service): bool
    {
        return $this->stackHasService($service);
    }

    public function callDatabaseEngine(): ?string
    {
        return $this->databaseEngine();
    }

    public function callContainerUser(): string
    {
        return $this->containerUser();
    }

    /**
     * @return array<string, string>
     */
    public function callEnvVariables(): array
    {
        return $this->envVariables();
    }

    /**
     * @param  array<string, mixed>  $service
     * @return array<string, mixed>
     */
    public function callSetServiceEnvironment(array $service, string $key, string $value): array
    {
        $this->setServiceEnvironment($service, $key, $value);

        return $service;
    }

    /**
     * @param  list<string>  $services
     */
    public function callBuildDockerCompose(array $services): void
    {
        $this->buildDockerCompose($services);
    }

    /**
     * @param  list<string>  $services
     */
    public function callReplaceEnvVariables(array $services): void
    {
        $this->replaceEnvVariables($services);
    }

    /**
     * @param  list<string>  $services
     */
    public function callPrepareInstallation(array $services): void
    {
        $this->prepareInstallation($services);
    }
}
