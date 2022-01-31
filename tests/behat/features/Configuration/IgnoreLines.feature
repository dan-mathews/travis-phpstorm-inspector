Feature: Run inspections with certain lines ignored

  Background:
    Given I create a new project
    And I pull docker image 'danmathews1/phpstorm:2021.1.2'
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git

  @issue-35 @positive @createsProject @createsProjectInStorage
  Scenario: Run inspections on a project with certain lines ignored
    Given I create a configuration file with:
    """
    {
      "ignore-lines": {
        "src/InspectionViolator.php": [
          10,
          19,
          37
        ]
      },
      "docker-tag": "2021.1.2"
    }
    """
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    33 problems were found during PhpStorm inspection.

    Problems in file:///{{ projectRoot }}/src/InspectionViolator.php:
      line 1    ERROR         (Short open tag usage) Short opening tag usage
      line 5    ERROR         (Undefined class) Undefined class 'NonExistent'
      line 14   TYPO          (Typo) Typo: In word 'propertie'
      line 15   TYPO          (Typo) Typo: In word 'propertie'
      line 16   TYPO          (Typo) Typo: In word 'propertie'
      line 16   WARNING       (Language level) Typed properties are only allowed since PHP 7.4
      line 16   WARNING       (Missing PHPDoc comment) Missing PHPDoc comment for field
      line 16   WARNING       (Property name is not following coding convention) Property name <code>$bad_propertie</code> doesn't match regex '[a-z][A-Za-z\d]*' #loc
      line 22   WARNING       (Parameters number mismatch declaration) Method call is provided 1 parameters, but the method signature uses 0 parameters
      line 25   ERROR         (Method '__toString' implementation) Method '__toString' is not implemented for '\stdClass'
      line 34   WARNING       (PHPDoc comment matches function/method signature) PHPDoc for non-existing argument
      line 35   WARNING       (PHPDoc comment matches function/method signature) Return type does not match the declared
      line 41   ERROR         (Undefined variable) Undefined variable '$argument'
      line 41   WARNING       (Statement has empty body) Statement has empty body
      line 45   WARNING       (Expression result unused) Expression result is not used anywhere
      line 48   ERROR         (Annotator) A void function must not return a value #loc
      line 57   ERROR         (Undefined class) Undefined class 'InvalidData'
      line 59   ERROR         (Unused private method) Unused private method 'unused'
      line 59   WARNING       (unused declaration) <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 59   WEAK WARNING  (Missing return type declaration) Missing function's return type declaration
      line 66   WARNING       (Missing @throws tag(s)) PHPDoc comment doesn't contain all the necessary @throws tags
      line 68   WARNING       (Missing @throws tag(s)) PHPDoc comment doesn't contain all the necessary @throws tags
      line 69   WARNING       (Language level) Union types are only allowed since PHP 8.0
      line 69   WARNING       (unused declaration) <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 76   WARNING       (Unused local variable) Unused local variable 'item'. The value of the variable is not used anywhere.
      line 76   WEAK WARNING  (Array is always empty at the point of access) Array is always empty at this point
      line 79   WEAK WARNING  (Unhandled exception) Unhandled exceptions
      line 82   WARNING       (Unnecessary semicolon) Unnecessary ;
      line 85   ERROR         (Invalid argument supplied for 'foreach()') Invalid argument supplied to 'foreach'
      line 89   WARNING       (Inconsistent return points) Missing 'return' statement
      line 95   ERROR         (Annotator) Another definition with same name exists in this file #loc
      line 95   ERROR         (Annotator) Class should not extend itself #loc
      line 95   WEAK WARNING  (Multiple class declarations) Multiple definitions exist for class 'InspectionViolator'
    """

  @issue-35 @positive @createsProject @createsProjectInStorage
  Scenario: Run inspections on a project with all lines of a file ignored
    Given I create a configuration file with:
    """
    {
      "ignore-lines": {
        "src/InspectionViolator.php": ["*"]
      },
      "docker-tag": "2021.1.2"
    }
    """
    When I run inspections
    Then the exit code should be 0
    And the last lines of the output should be:
    """
    No problems to report.
    """
