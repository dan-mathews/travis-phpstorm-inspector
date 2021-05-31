<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\FileCreator;

class ProfileSettingsXml implements CreateInterface
{
    private const NAME = 'profiles_settings.xml';

    /**
     * @var FileCreator
     */
    private $fileCreator;

    /**
     * @var string
     */
    private $profileName;

    public function __construct(FileCreator $fileCreator, string $profileName)
    {
        $this->fileCreator = $fileCreator;

        $this->profileName = $profileName;
    }

    private function getContents(): string
    {
        //PhpStorm creates this without an XML declaration, so we do the same
        return '<component name="InspectionProjectProfileManager">'
        . '<settings><option name="PROJECT_PROFILE" value="' . $this->profileName . '" />'
        . '<version value="1.0" />'
        . '</settings>'
        . '</component>';
    }

    public function create(string $location): void
    {
        $this->fileCreator->createFile($location, self::NAME, $this->getContents());
    }
}
