<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableFile;

class InspectionsXml extends AbstractCreatableFile
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

    /**
     * @param string $inspectionsXmlPath
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param string $inspectionsXmlPath
     * @return string
     * @throws \InvalidArgumentException
     */
    private function extractProfileNameValue(string $inspectionsXmlPath): string
    {
        $xml = new \XMLReader();

        /** @noinspection PhpStaticAsDynamicMethodCallInspection this method doesn't work statically */
        /** @noinspection StaticInvocationViaThisInspection as above */
        $xml->open($inspectionsXmlPath);

        while ($xml->read()) {
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

        $xml->close();

        throw new \InvalidArgumentException(
            'Could not read a "myName" attribute from the inspections profile,'
            . ' so the profile could not be referenced elsewhere.'
        );
    }

    /**
     * @param string $inspectionsXmlPath
     * @return \SplFileInfo
     * @throws \InvalidArgumentException
     */
    private function validateInspectionsXml(string $inspectionsXmlPath): \SplFileInfo
    {
        $inspectionsXmlInfo = new \SplFileInfo($inspectionsXmlPath);

        if (!$inspectionsXmlInfo->isReadable()) {
            throw new \InvalidArgumentException('Could not read the inspections profile at ' . $inspectionsXmlPath);
        }

        if ('xml' !== $inspectionsXmlInfo->getExtension()) {
            throw new \InvalidArgumentException(
                'The inspections profile at ' . $inspectionsXmlPath . ' does not have an xml extension'
            );
        }

        return $inspectionsXmlInfo;
    }

    /**
     * @param \SplFileInfo $inspectionsXmlInfo
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getInspectionsXmlContents(\SplFileInfo $inspectionsXmlInfo): string
    {
        if (false === $inspectionsXmlInfo->getRealPath()) {
            throw new \InvalidArgumentException('Could not read the path of inspections profile');
        }

        $contents = file_get_contents($inspectionsXmlInfo->getRealPath());

        if (false === $contents) {
            throw new \InvalidArgumentException(
                'Could not read the contents of inspections profile ' . $inspectionsXmlInfo->getRealPath()
            );
        }

        return $contents;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function getContents(): string
    {
        return $this->contents;
    }
}
