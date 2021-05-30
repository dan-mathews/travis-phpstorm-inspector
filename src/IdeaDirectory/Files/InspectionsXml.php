<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class InspectionsXml extends AbstractFile
{
    /**
     * @var string
     */
    private $contents = '';

    /**
     * @var string
     */
    private $name = '';

    public function setContentsFromPath(string $inspectionsXmlPath): void
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

        $contents = file_get_contents($inspectionsXmlInfo->getRealPath());

        if (false === $contents) {
            echo 'Could not read the contents of inspections profile ' . $inspectionsXmlPath;
            exit(1);
        }

        $this->contents = $contents;

        $this->name = $inspectionsXmlInfo->getFilename();
    }

    protected function getContents(): string
    {
        return $this->contents;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
