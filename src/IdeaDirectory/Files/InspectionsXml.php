<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class InspectionsXml extends AbstractFile
{
    /**
     * @var string
     */
    private $contents;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $profileNameValue;

    public function __construct(string $inspectionsXmlPath)
    {
        $inspectionsXmlInfo = $this->validateInspectionsXml($inspectionsXmlPath);

        $this->contents = $this->getInspectionsXmlContents($inspectionsXmlInfo);

        $this->name = $inspectionsXmlInfo->getFilename();

        $this->profileNameValue = $this->extractProfileNameValue($inspectionsXmlPath);
    }

    public function getProfileNameValue(): string
    {
        return $this->profileNameValue;
    }

    private function extractProfileNameValue(string $inspectionsXmlPath): string
    {
        $xml = new \XMLReader();

        /** @noinspection PhpStaticAsDynamicMethodCallInspection this method doesn't work statically */
        /** @noinspection StaticInvocationViaThisInspection as above */
        $xml->open($inspectionsXmlPath);

        while($xml->read()){
            if (
                $xml->nodeType === $xml::ELEMENT &&
                $xml->name === 'option' &&
                $xml->getAttribute('name') === 'myName'
            ) {
                $value = $xml->getAttribute('value');

                if (null !== $value) {
                    return $value;
                }
            }
        }

        echo 'Could not read a "myName" attribute from the inspections profile,'
        . ' so the profile could not be referenced elsewhere.';

        $xml->close();

        exit(1);
    }

    private function validateInspectionsXml(string $inspectionsXmlPath): \SplFileInfo
    {
        $inspectionsXmlInfo = new \SplFileInfo($inspectionsXmlPath);

        if (!$inspectionsXmlInfo->isReadable()) {
            echo 'Could not read the inspections profile at ' . $inspectionsXmlPath;
            exit(1);
        }

        if ('xml' !== $inspectionsXmlInfo->getExtension()) {
            echo 'The inspections profile at ' . $inspectionsXmlPath . ' does not have an xml extension';
            exit(1);
        }

        return $inspectionsXmlInfo;
    }

    private function getInspectionsXmlContents(\SplFileInfo $inspectionsXmlInfo): string
    {
        $contents = file_get_contents($inspectionsXmlInfo->getRealPath());

        if (false === $contents) {
            echo 'Could not read the contents of inspections profile ' . $inspectionsXmlInfo->getRealPath();
            exit(1);
        }

        return $contents;
    }

    protected function getName(): string
    {
        return $this->name;
    }

    protected function getContents(): string
    {
        return $this->contents;
    }
}
