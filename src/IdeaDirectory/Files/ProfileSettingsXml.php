<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class ProfileSettingsXml extends AbstractFile
{
    private const NAME = 'profiles_settings.xml';

    /**
     * @var string
     */
    private $profileName;

    public function __construct(string $profileName)
    {
        $this->profileName = $profileName;
    }

    protected function getContents(): string
    {
        //PhpStorm creates this without an XML declaration, so we do the same
        return '<component name="InspectionProjectProfileManager">'
        . '<settings><option name="PROJECT_PROFILE" value="' . $this->profileName . '" />'
        . '<version value="1.0" />'
        . '</settings>'
        . '</component>';
    }

    protected function getName(): string
    {
        return self::NAME;
    }
}
