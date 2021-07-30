<?php

namespace BehatTests\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use LogicException;
use PHPUnit\Framework\Assert;
use TravisPhpstormInspector\App;
use TravisPhpstormInspector\ResultProcessing\InspectionOutcome;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var null|string
     */
    private $projectPath;

    /**
     * @var null|string
     */
    private $inspectionsPath;

    /**
     * @var null|string
     */
    private $projectName;

    /**
     * @var null|InspectionOutcome
     */
    private $inspectionOutcome;

    /**
     * @var null|string
     */
    private $phpFilePath;

    /**
     * @var bool
     */
    private $expectingError = false;

    /**
     * @var null|string
     */
    private $errorMessage;


    /**
     * @Given I create a new project
     */
    public function iCreateANewProject(): void
    {
        $this->projectName = 'testProject' . random_int(0, 9999);

        if (!mkdir($this->projectName) && !is_dir($this->projectName)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $this->projectName));
        }

        $this->projectPath = realpath($this->projectName);
    }

    private function getProjectPath(): string
    {
        if (null === $this->projectPath) {
            throw new LogicException(
                'Project path must be defined before it is retrieved'
            );
        }

        return $this->projectPath;
    }

    private function getErrorMessage(): string
    {
        if (null === $this->errorMessage) {
            throw new LogicException(
                'Error message must be defined before it is retrieved'
            );
        }

        return $this->errorMessage;
    }

    private function getInspectionsPath(): string
    {
        if (null === $this->inspectionsPath) {
            throw new LogicException(
                'Inspections path must be defined before it is retrieved'
            );
        }

        return $this->inspectionsPath;
    }

    private function getPhpFilePath(): string
    {
        if (null === $this->phpFilePath) {
            throw new LogicException(
                'Php file path must be defined before it is retrieved'
            );
        }

        return $this->phpFilePath;
    }

    private function getInspectionOutcome(): InspectionOutcome
    {
        if (null === $this->inspectionOutcome) {
            throw new LogicException(
                'Inspection outcome must be defined before it is retrieved'
            );
        }

        return $this->inspectionOutcome;
    }

    /**
     * @Given I create a :valid inspections xml file
     * @Given I create an :valid inspections xml file
     */
    public function iCreateAInspectionsXmlFile(string $valid): void
    {
        switch ($valid) {
            case 'valid':
                $xmlContents = file_get_contents('tests/data/exampleStandards.xml');

                $this->inspectionsPath = $this->getProjectPath() . '/exampleStandards.xml';

                break;
            case 'invalid':
                $xmlContents = 'invalid';

                $this->inspectionsPath = $this->getProjectPath() . '/invalid.txt';

                break;
            default:
                throw new LogicException(
                    'Inspections file referenced for test must be valid or invalid'
                );
        }

        $file = fopen($this->inspectionsPath, 'wb');

        if(!fwrite($file, $xmlContents)) {
            throw new \RuntimeException($this->inspectionsPath . ' could not be created');
        }

        fclose($file);
    }

    /**
     * @Given I create a php file :switch violations
     */
    public function iCreateAPhpFileWithNoViolations(string $switch): void
    {
        switch ($switch) {
            case 'without':
                $filename = 'Clean.php';
                break;
            case 'with':
                $filename = 'InspectionViolator.php';
                break;
            default:
                throw new \LogicException('This method can only be called "with" or "without" violations');
        }

        $phpContents = file_get_contents('tests/data/' . $filename);

        $path = $this->getProjectPath() . '/' . $filename;

        $file = fopen($path, 'wb');

        if(!fwrite($file, $phpContents)) {
            throw new \RuntimeException($path . ' could not be created');
        }

        fclose($file);

        $this->phpFilePath = realpath($path);
    }

    /**
     * @When I run inspections
     */
    public function iRunInspections(): void
    {
        try {
            $app = new App($this->getProjectPath(), $this->getInspectionsPath());

            $this->inspectionOutcome = $app->run();
        } catch (\Throwable $e) {
            if (!$this->expectingError) {
                throw $e;
            }

            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * @Given I am expecting an error
     *
     * @return void
     */
    public function iAmExpectingAnError(): void
    {
        $this->expectingError = true;
    }

    /**
     * @Then the error message should contain :string
     *
     * @return void
     */
    public function theErrorMessageShouldContain(string $string): void
    {
        Assert::assertStringContainsString($string, $this->getErrorMessage());
    }


    /**
     * @Then the outcome exit code should be :exitCode
     */
    public function theOutcomeExitCodeShouldBe(string $exitCode): void
    {
        Assert::assertSame((int) $exitCode, $this->getInspectionOutcome()->getExitCode());
    }

    /**
     * @Given I initialise git
     */
    public function iInitialiseGit(): void
    {
        $code = 1;

        exec('git init ' . $this->getProjectPath(), $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Could not initialise git in ' . $this->getProjectPath());
        }
    }

    /**
     * @Given I stage the php file in git
     */
    public function iStageThePhpFileInGit(): void
    {
        $currentPath = getcwd();

        if(!chdir($this->getProjectPath())) {
            throw new \RuntimeException('Could not change directory into the project');
        }

        $code = 1;

        exec('git add ' . $this->getPhpFilePath(), $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Could not stage the php file in ' . $this->getProjectPath());
        }

        chdir($currentPath);
    }

    /**
     * @Then /^the outcome message should be:$/
     *
     * @return void
     */
    public function theOutcomeMessageShouldBe(PyStringNode $string): void
    {
        Assert::assertSame($string->getRaw(), trim($this->getInspectionOutcome()->getMessage()));
    }

    /**
     * @Then there should only be :expectedFileCount items in the project directory
     */
    public function thereShouldOnlyBeItemsInTheProjectDirectory(int $expectedFileCount): void
    {
        $files = scandir($this->getProjectPath());

        $actualFileCount = count($files) - 2;

        Assert::assertSame($expectedFileCount, $actualFileCount);
    }


    /**
      * @AfterScenario @createsProject
      *
      * @return void
      */
     public function cleanProject()
     {
        //  if (!is_dir($this->getProjectPath())) {
        //     return;
        // }
        //
        // $this->removeDirectory(new \DirectoryIterator($this->getProjectPath()));
    }

     private function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {

            if ($info->isDot()) {
                continue;
            }

            if ($info->isDir()) {
                self::removeDirectory(new \DirectoryIterator($info->getRealPath()));
                continue;
            }

            if ($info->isFile()) {
                unlink($info->getRealPath());
            }
        }

        rmdir($directoryIterator->getPath());
    }
}
