<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\FileContents\GetContentsInterface;

class InspectionsXml implements GetContentsInterface
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
     * @throws InspectionsProfileException
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
     * @throws InspectionsProfileException
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

        throw new InspectionsProfileException(
            'Could not read a "myName" attribute from the inspections profile,'
            . ' so the profile could not be referenced elsewhere.'
        );
    }

    /**
     * @param string $inspectionsXmlPath
     * @return \SplFileInfo
     * @throws InspectionsProfileException
     */
    private function validateInspectionsXml(string $inspectionsXmlPath): \SplFileInfo
    {
        if (!file_exists($inspectionsXmlPath)) {
            throw new InspectionsProfileException(
                'The inspections profile at ' . $inspectionsXmlPath . ' does not exist.'
            );
        }

        $inspectionsXmlInfo = new \SplFileInfo($inspectionsXmlPath);

        if (!$inspectionsXmlInfo->isReadable()) {
            throw new InspectionsProfileException(
                'Could not read the inspections profile ' . $inspectionsXmlInfo->getFilename()
            );
        }

        if ('xml' !== $inspectionsXmlInfo->getExtension()) {
            throw new InspectionsProfileException(
                'The inspections profile ' . $inspectionsXmlInfo->getFilename() . ' does not have an xml extension.'
            );
        }

        return $inspectionsXmlInfo;
    }

    /**
     * @param \SplFileInfo $inspectionsXmlInfo
     * @return string
     * @throws InspectionsProfileException
     */
    private function getInspectionsXmlContents(\SplFileInfo $inspectionsXmlInfo): string
    {
        if (false === $inspectionsXmlInfo->getRealPath()) {
            throw new InspectionsProfileException(
                'Could not read the path of inspections profile ' . $inspectionsXmlInfo->getFilename()
            );
        }

        $contents = file_get_contents($inspectionsXmlInfo->getRealPath());

        if (false === $contents) {
            throw new InspectionsProfileException(
                'Could not read the contents of inspections profile ' . $inspectionsXmlInfo->getFilename()
            );
        }

        return $contents;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContents(): string
    {
        return $this->contents;
    }
}
