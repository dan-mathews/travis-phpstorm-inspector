<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\FileCreator;

class InspectionsXml implements CreateInterface
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
     * @var FileCreator
     */
    private $fileCreator;

    /**
     * @var string
     */
    private $profileNameValue;

    /**
     * @var null|string
     */
    private $path;

    public function __construct(FileCreator $fileCreator, string $inspectionsXmlPath)
    {
        $this->fileCreator = $fileCreator;

        $inspectionsXmlInfo = $this->validateInspectionsXml($inspectionsXmlPath);

        $this->contents = $this->getInspectionsXmlContents($inspectionsXmlInfo);

        $this->name = $inspectionsXmlInfo->getFilename();

        $this->profileNameValue = $this->extractProfileNameValue($inspectionsXmlPath);
    }

    public function getPath(): string
    {
        if (null === $this->path){
            echo $this->name . ' must be created before the path is retrieved.';
            exit(1);
        }

        return $this->path;
    }

    public function getProfileNameValue(): string
    {
        return $this->profileNameValue;
    }

    public function create(string $location): void
    {
        $this->fileCreator->createFile($location, $this->name, $this->contents);

        $this->path = $location . '/' . $this->name;
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
}
