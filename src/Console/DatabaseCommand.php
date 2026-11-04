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

namespace Corsair\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Backs `db:query`, `db:dump`, and `db:import`, run directly against the
 * installed `mysql` or `mariadb` service using the credentials Corsair
 * already wrote to `.env`.
 */
class DatabaseCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    public function query(): int
    {
        $this->ensureIo();

        $engine = $this->requireDatabaseEngine();

        if ($engine === null) {
            return self::FAILURE;
        }

        $env = $this->envVariables();
        $command = $this->dockerComposeExec(
            $this->clientArguments($this->clientBinary($engine), $env),
            $engine,
        );

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);

            return self::SUCCESS;
        }

        return $this->runCommands([$command]) === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<string>  $arguments
     */
    public function dump(array $arguments): int
    {
        $this->ensureIo();

        $engine = $this->requireDatabaseEngine();

        if ($engine === null) {
            return self::FAILURE;
        }

        $env = $this->envVariables();
        $database = $env['DB_DATABASE'] ?? 'database';
        $destination = $arguments[0] ?? $this->basePath($database . '-' . date('Y-m-d_His') . '.sql');

        $command = $this->dockerComposeExec(
            $this->clientArguments($this->dumpBinary($engine), $env),
            $engine,
            true,
        );

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command . ' > ' . $destination);

            return self::SUCCESS;
        }

        $handle = fopen($destination, 'w');

        if ($handle === false) {
            $this->error('Could not open ' . $destination . ' for writing.');

            return self::FAILURE;
        }

        $process = Process::fromShellCommandline($command, $this->basePath());
        $process->setTimeout(null);

        $status = $process->run(static function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            } else {
                fwrite(\STDERR, $buffer);
            }
        });

        fclose($handle);

        if ($status !== 0) {
            $this->error('Database dump failed.');

            return self::FAILURE;
        }

        $this->info('Database dumped to ' . $destination . '.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $arguments
     */
    public function import(array $arguments): int
    {
        $this->ensureIo();

        $engine = $this->requireDatabaseEngine();

        if ($engine === null) {
            return self::FAILURE;
        }

        $source = $arguments[0] ?? null;

        if ($source === null) {
            $this->error('Provide an SQL file to import: vendor/bin/corsair db:import <file.sql>');

            return self::FAILURE;
        }

        $sourcePath = $this->basePath($source);

        if (! is_file($sourcePath)) {
            $this->error('No such file: ' . $source);

            return self::FAILURE;
        }

        $env = $this->envVariables();
        $command = $this->dockerComposeExec(
            $this->clientArguments($this->clientBinary($engine), $env),
            $engine,
            true,
        );

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command . ' < ' . $source);

            return self::SUCCESS;
        }

        $process = Process::fromShellCommandline($command, $this->basePath());
        $process->setTimeout(null);
        $process->setInput(fopen($sourcePath, 'r'));

        $status = $process->run(function (string $type, string $buffer): void {
            if ($type === Process::ERR) {
                $this->output->write($buffer);
            }
        });

        if ($status !== 0) {
            $this->error('Database import failed.');

            return self::FAILURE;
        }

        $this->info('Imported ' . $source . '.');

        return self::SUCCESS;
    }

    private function requireDatabaseEngine(): ?string
    {
        if (! is_file($this->composePath())) {
            $this->error('No Compose file found. Run `corsair:install` first.');

            return null;
        }

        $engine = $this->databaseEngine();

        if ($engine === null) {
            $this->error('No mysql or mariadb service is installed. Run `corsair:add mysql` (or `corsair:add mariadb`) first.');

            return null;
        }

        return $engine;
    }

    private function clientBinary(string $engine): string
    {
        return $engine === 'mariadb' ? 'mariadb' : 'mysql';
    }

    private function dumpBinary(string $engine): string
    {
        return $engine === 'mariadb' ? 'mariadb-dump' : 'mysqldump';
    }

    /**
     * @param  array<string, string>  $env
     * @return list<string>
     */
    private function clientArguments(string $binary, array $env): array
    {
        $arguments = [$binary, '-u' . ($env['DB_USERNAME'] ?? 'corsair')];

        if (($env['DB_PASSWORD'] ?? '') !== '') {
            $arguments[] = '-p' . $env['DB_PASSWORD'];
        }

        $arguments[] = (string) ($env['DB_DATABASE'] ?? '');

        return $arguments;
    }
}
