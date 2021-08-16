<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class InspectionConfigurationParser
{
    private const FILENAME = 'travis-phpstorm-inspector.json';

    private const KEY_IGNORED_SEVERITIES = 'ignored_severities';

    /**
     * @var string
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return InspectionConfiguration
     * @throws \InvalidArgumentException
     */
    public function parse(): InspectionConfiguration
    {
        $inspectionConfiguration = new InspectionConfiguration();

        $path = $this->project->getPath() . '/' . self::FILENAME;

        if (!file_exists($path)) {
            return $inspectionConfiguration;
        }

        $configurationContents = file_get_contents($path);

        if (false === $configurationContents) {
            throw new \InvalidArgumentException('Could not read the configuration file.');
        }

        try {
            $parsedConfiguration = json_decode($configurationContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(
                'Could not process the configuration file as json.',
                1,
                $e
            );
        }

        try {
            $inspectionConfiguration->setIgnoredSeverities($parsedConfiguration[self::KEY_IGNORED_SEVERITIES] ?? []);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                'Could not process the ignored severities in configuration file.',
                1,
                $e
            );
        }

        return $inspectionConfiguration;
    }
}
