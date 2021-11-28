<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Inspection;
use TravisPhpstormInspector\Views\Fail;
use TravisPhpstormInspector\Views\Error;
use TravisPhpstormInspector\Views\Pass;

class InspectCommand extends Command
{
    public const NAME = 'travis-phpstorm-inspector';

    public const ARGUMENT_PROJECT_PATH = 'project-path';

    public const OPTION_CONFIGURATION = 'configuration';
    public const OPTION_DOCKER_REPOSITORY = 'docker-repository';
    public const OPTION_DOCKER_TAG = 'docker-tag';
    public const OPTION_EXCLUDE_FOLDERS = 'exclude-folders';
    public const OPTION_IGNORE_SEVERITIES = 'ignore-severities';
    public const OPTION_IGNORE_LINES = 'ignore-lines';
    public const OPTION_PHP_VERSION = 'php-version';
    public const OPTION_INSPECTION_PROFILE = 'profile';
    public const OPTION_WHOLE_PROJECT = 'whole-project';

    public const FLAG_VERBOSE = 'verbose';

    public const OPTIONS = [
        self::OPTION_CONFIGURATION,
        self::OPTION_DOCKER_REPOSITORY,
        self::OPTION_DOCKER_TAG,
        self::OPTION_EXCLUDE_FOLDERS,
        self::OPTION_IGNORE_SEVERITIES,
        self::OPTION_IGNORE_LINES,
        self::OPTION_PHP_VERSION,
        self::OPTION_INSPECTION_PROFILE,
        self::OPTION_WHOLE_PROJECT
    ];

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'inspect';

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_PROJECT_PATH,
            InputArgument::OPTIONAL,
            'The absolute or relative path of the project to inspect' . PHP_EOL
            . '- default: the current working directory'
        );

        $this->addOption(
            self::OPTION_CONFIGURATION,
            null,
            InputOption::VALUE_OPTIONAL,
            'The absolute path of the configuration file to use' . PHP_EOL
            . '- default: If it exists, the configuration file in the project root'
        );

        $this->addOption(
            self::OPTION_INSPECTION_PROFILE,
            null,
            InputOption::VALUE_OPTIONAL,
            'The absolute or relative path of the inspection profile to use' . PHP_EOL
            . '- default: PhpStorm\'s default profile, see ' . Configuration::DEFAULT_INSPECTION_PROFILE_PATH
        );

        $this->addOption(
            self::OPTION_IGNORE_SEVERITIES,
            null,
            InputOption::VALUE_OPTIONAL,
            'The severities to ignore, as a comma-separated list without spaces e.g. \'TYPO\',\'INFORMATION\''
            . PHP_EOL . '- default: ' . var_export(implode(',', Configuration::DEFAULT_IGNORE_SEVERITIES), true)
        );

        $this->addOption(
            self::OPTION_DOCKER_REPOSITORY,
            null,
            InputOption::VALUE_OPTIONAL,
            'The name of the docker repository to use, containing a PhpStorm image' . PHP_EOL
            . '- default: ' . Configuration::DEFAULT_DOCKER_REPOSITORY
        );

        $this->addOption(
            self::OPTION_DOCKER_TAG,
            null,
            InputOption::VALUE_OPTIONAL,
            'The docker tag to use, referencing a PhpStorm image in the docker repository' . PHP_EOL
            . '- default: ' . Configuration::DEFAULT_DOCKER_TAG
        );

        $this->addOption(
            self::OPTION_PHP_VERSION,
            null,
            InputOption::VALUE_OPTIONAL,
            'The php version to use' . PHP_EOL
            . '- default: ' . Configuration::DEFAULT_PHP_VERSION
        );

        $this->addOption(
            self::OPTION_WHOLE_PROJECT,
            null,
            InputOption::VALUE_NONE,
            'Inspect the whole project rather than just the local uncommitted changes'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $workingDirectory = $this->getWorkingDirectory();

            $configurationBuilder = new ConfigurationBuilder(
                $input->getArguments(),
                $input->getOptions(),
                $workingDirectory,
                $output
            );

            $configurationBuilder->build();
            $configuration = $configurationBuilder->getResult();

            $inspection = new Inspection($configuration, $output);

            $problems = $inspection->run();

            if (!$problems->isEmpty()) {
                $view = new Fail($problems);

                $view->display();

                /**
                 * @var int
                 * @psalm-suppress UndefinedConstant - psalm is not detecting symfony's Command constants.
                 */
                return Command::FAILURE;
            }

            $view = new Pass();

            $view->display();

            /**
             * @var int
             * @psalm-suppress UndefinedConstant - psalm is not detecting symfony's Command constants.
             */
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            // We default to verbose if the ConfigurationBuilder wasn't successfully constructed.
            /** @noinspection UnSafeIsSetOverArrayInspection - We need to check if it's set, rather than null. */
            $verbose = !isset($configurationBuilder) || $configurationBuilder->getResult()->getVerbose();

            $view = new Error($e, $verbose);

            $view->display();

            /**
             * @var int
             * @psalm-suppress UndefinedConstant - psalm is not detecting symfony's Command constants.
             */
            return Command::INVALID;
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function getWorkingDirectory(): string
    {
        $workingDirectory = getcwd();

        if (false === $workingDirectory) {
            throw new \RuntimeException('Could not establish current working directory. Does the current, or any parent'
                . ' directory, not have the readable or search mode set?');
        }

        return $workingDirectory;
    }
}
