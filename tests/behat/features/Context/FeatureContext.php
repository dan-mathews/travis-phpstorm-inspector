<?php

namespace BehatTests\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use LogicException;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * This is relative to the project root
     *
     * @var null|string
     */
    private $projectPath;

    /**
     * This is relative to the project root
     *
     * @var null|string
     */
    private $inspectionsPath;

    /**
     * @var null|string
     */
    private $configurationPath;

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
     * @var null|int
     */
    private $inspectionExitCode;

    /**
     * @var null|string[]
     */
    private $inspectionOutput;


    /**
     * @Given I create a new project
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function iCreateANewProject(): void
    {
        $projectName = 'testProject' . random_int(0, 9999);

        $this->makeDirectory($projectName);

        $this->projectPath = $this->getRealPath($projectName);
    }

    /**
     * @param string $path
     * @throws \RuntimeException
     */
    private function makeDirectory(string $path): void
    {
        if (!mkdir($path) || !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $path));
        }
    }

    /**
     * @Given I have local .idea directory with a file in it
     * @throws \RuntimeException
     */
    public function iPutAFileInsideTheLocalIdeaDirectory(): void
    {
        $this->makeDirectory($this->getProjectPath() . '/.idea');

        $this->writeToFile($this->getProjectPath() . '/.idea/vitalFile.txt', 'vitally important is not deleted');
    }

    /**
     * @Then the local .idea directory should be unchanged
     */
    public function theLocalIdeaDirectoryShouldBeUnchanged(): void
    {
        Assert::assertEquals(
            [
                0 => '.',
                1 => '..',
                2 => 'vitalFile.txt'
            ],
            scandir($this->getProjectPath() . '/.idea')
        );
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

    private function getInspectionExitCode(): int
    {
        if (null === $this->inspectionExitCode) {
            throw new LogicException(
                'Inspection exit code must be defined before it is retrieved'
            );
        }

        return $this->inspectionExitCode;
    }

    /**
     * @return string[]
     */
    private function getInspectionOutput(): array
    {
        if (null === $this->inspectionOutput) {
            throw new LogicException(
                'Inspection output must be defined before it is retrieved'
            );
        }

        return $this->inspectionOutput;
    }

    private function getConfigurationPath(): string
    {
        if (null === $this->configurationPath) {
            throw new LogicException(
                'Configuration path must be defined before it is retrieved'
            );
        }

        return $this->configurationPath;
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

    /**
     * @Given I create a :valid inspections xml file
     * @Given I create an :valid inspections xml file
     */
    public function iCreateAInspectionsXmlFile(string $valid): void
    {
        switch ($valid) {
            case 'valid':
                $xmlContents = $this->readFromFile('tests/data/exampleStandards.xml');

                $this->inspectionsPath = 'exampleStandards.xml';

                break;
            case 'invalid':
                $xmlContents = 'invalid';

                $this->inspectionsPath = 'invalid.txt';

                break;
            default:
                throw new LogicException(
                    'Inspections file referenced for test must be valid or invalid'
                );
        }

        $this->writeToFile($this->getProjectPath() . '/' . $this->inspectionsPath, $xmlContents);
    }

    private function readFromFile(string $path): string
    {
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new \RuntimeException('Could not get contents of ' . $path);
        }

        return $contents;
    }

    /**
     * @Given I create a php file :switch problems
     */
    public function iCreateAPhpFileWithNoProblems(string $switch): void
    {
        switch ($switch) {
            case 'without':
                $filename = 'Clean.php';
                break;
            case 'with':
                $filename = 'InspectionViolator.php';
                break;
            default:
                throw new \LogicException('This method can only be called "with" or "without" problems');
        }

        $phpContents = $this->readFromFile('tests/data/' . $filename);

        $this->makeDirectory($this->getProjectPath() . '/src');

        $this->phpFilePath = $this->writeToFile($this->getProjectPath() . '/src/' . $filename, $phpContents);
    }

    private function writeToFile(string $path, string $contents): string
    {
        $file = fopen($path, 'wb');

        if (false === $file) {
            throw new \RuntimeException('Failed to create file at path: "' . $path . '".');
        }

        if (false === fwrite($file, $contents)) {
            throw new \RuntimeException('Failed to write to file at path: "' . $path . '".');
        }

        if (false === fclose($file)) {
            throw new \RuntimeException('Failed to close file at path: "' . $path . '".');
        }

        return $this->getRealPath($path);
    }

    private function getRealPath(string $path): string
    {
        $realPath = realpath($path);

        if (false === $realPath) {
            throw new \RuntimeException('Failed to find real path of : "' . $path . '".');
        }

        return $realPath;
    }

    /**
     * @When I run inspections
     * @psalm-suppress MixedPropertyTypeCoercion - We know $output will be an array of strings
     */
    public function iRunInspections(): void
    {
        exec(
            'bin/inspector inspect '
            . $this->getProjectPath() . ' '
            . '--profile ' . $this->getProjectPath() . '/' . $this->getInspectionsPath(),
            $this->inspectionOutput,
            $this->inspectionExitCode
        );
    }

    /**
     * @When I run inspections with help option
     * @psalm-suppress MixedPropertyTypeCoercion - We know $output will be an array of strings
     */
    public function iRunInspectionsWithHelpOption(): void
    {
        exec('bin/inspector inspect -h', $this->inspectionOutput, $this->inspectionExitCode);
    }

    /**
     * @Then the last :arg1 lines of the output should contain :string
     */
    public function theLastLinesOfTheOutputShouldContain(int $arg1, string $string): void
    {
        $outputSnippet = array_slice($this->getInspectionOutput(), $arg1 * -1);

        foreach ($outputSnippet as $line) {
            if (false !== strpos($line, $string)) {
                Assert::assertStringContainsString($string, $line);
                return;
            }
        }

        Assert::fail("The last $arg1 lines were:\n" . implode("\n", $outputSnippet));
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
     * @Then the exit code should be :exitCode
     */
    public function theExitCodeShouldBe(string $exitCode): void
    {
        //todo - put this into a behat failure post hook?
        if ((int) $exitCode !== $this->getInspectionExitCode()) {
            echo "The last 40 lines of the output were:\n" . $this->getLastLinesOfOutput(40);
        }

        Assert::assertSame((int) $exitCode, $this->getInspectionExitCode());
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

        if (!chdir($this->getProjectPath())) {
            throw new \RuntimeException('Could not change directory into the project');
        }

        $code = 1;

        exec('git add ' . $this->getPhpFilePath(), $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Could not stage the php file in ' . $this->getProjectPath());
        }

        if (
            false === $currentPath ||
            !chdir($currentPath)
        ) {
            throw new \RuntimeException('Could not change directory back to original location');
        }
    }

    private function getLastLinesOfOutput(int $lineCount): string
    {
        return implode(
            "\n",
            array_slice($this->getInspectionOutput(), $lineCount * -1)
        );
    }

    /**
     * @Then the last lines of the output should be:
     */
    public function theLastLinesOfTheOutputShouldBe(PyStringNode $string): void
    {
        /** @psalm-suppress MixedArgument, UndefinedMethod this returns array which feeds into count() just fine */
        $assertedOutputLineCount = count($string->getStrings());

        $actualOutputLinesForComparison = $this->getLastLinesOfOutput($assertedOutputLineCount);

        $expectedString = $string->getRaw();

        // We could use Twig, but this is currently the only variable, so adding a new dependency would be overkill.
        if (false !== strpos($expectedString, '{{ projectRoot }}')) {
            $expectedString = str_replace('{{ projectRoot }}', $this->getProjectPath(), $string->getRaw());
        }

        if ($expectedString !== $actualOutputLinesForComparison) {
            echo "For debugging, the last 40 lines of output were:\n" . $this->getLastLinesOfOutput(40);
        }

        Assert::assertSame($expectedString, $actualOutputLinesForComparison);
    }

    /**
     * @Given I create a configuration file with:
     */
    public function iCreateAConfigurationFileWith(PyStringNode $string): void
    {
        $this->configurationPath = $this->getProjectPath() . '/travis-phpstorm-inspector.json';

        $this->writeToFile($this->configurationPath, $string->getRaw());
    }

    /**
      * @AfterScenario @createsProject
      *
      * @return void
      */
    public function cleanProject(): void
    {
        if (!is_dir($this->getProjectPath())) {
            return;
        }

        $this->removeDirectory(new \DirectoryIterator($this->getProjectPath()));
    }

    private function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {
            if ($info->isDot()) {
                continue;
            }

            $realPath = $info->getRealPath();

            if (false === $realPath) {
                throw new \RuntimeException('Could not get real path of ' . var_export($info, true));
            }

            if ($info->isDir()) {
                self::removeDirectory(new \DirectoryIterator($realPath));
                continue;
            }

            if ($info->isFile()) {
                unlink($realPath);
            }
        }

        rmdir($directoryIterator->getPath());
    }
}
