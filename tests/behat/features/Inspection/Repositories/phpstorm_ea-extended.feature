Feature: Run inspections

  Background:
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a configuration file with:
    """
    {
      "docker-repository": "danmathews1/phpstorm_ea-extended",
      "docker-tag": "2021.1.2_4.0.6.4"
    }
    """

  @issue-12 @positive @createsProject @createsProjectInStorage
  Scenario: Run inspections on a project with no problems
    Given I create a php file without problems
    And I stage the php file in git
    When I run inspections
    Then the exit code should be 0
    And the last lines of the output should be:
    """
    No problems to report.
    """

  @issue-12 @positive @createsProject @createsProjectInStorage
  Scenario: Run inspections on a project with problems
    Given I create a php file with problems
    And I stage the php file in git
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    49 problems were found during PhpStorm inspection.

    Problems in file:///{{ projectRoot }}/src/InspectionViolator.php:
      line 1    ERROR         (PHP short open tag usage) [EA] Using the '<?' short tag considered to be a bad practice
      line 1    ERROR         (Short open tag usage) Short opening tag usage
      line 5    ERROR         (Undefined class) Undefined class 'NonExistent'
      line 10   WARNING       (Access modifiers shall be defined) [EA] 'badConstant' should be declared with access modifier.
      line 10   WARNING       (Constant name is not following coding convention) Constant name <code>badConstant</code> doesn't match regex '[A-Z][A-Z_\d]*' #loc
      line 10   WARNING       (Missing visibility) PSR-12: Missing visibility definition
      line 10   WARNING       (unused declaration) Constant is never used.
      line 14   TYPO          (Typo) Typo: In word 'propertie'
      line 15   TYPO          (Typo) Typo: In word 'propertie'
      line 16   TYPO          (Typo) Typo: In word 'propertie'
      line 16   WARNING       (Language level) Typed properties are only allowed since PHP 7.4
      line 16   WARNING       (Missing PHPDoc comment) Missing PHPDoc comment for field
      line 16   WARNING       (Property name is not following coding convention) Property name <code>$bad_propertie</code> doesn't match regex '[a-z][A-Za-z\d]*' #loc
      line 19   WARNING       (unused declaration) Constructor is never used.
      line 22   WARNING       (Parameters number mismatch declaration) Method call is provided 1 parameters, but the method signature uses 0 parameters
      line 25   ERROR         (Method '__toString' implementation) Method '__toString' is not implemented for '\stdClass'
      line 34   WARNING       (PHPDoc comment matches function/method signature) PHPDoc for non-existing argument
      line 35   WARNING       (PHPDoc comment matches function/method signature) Return type does not match the declared
      line 37   ERROR         (Annotator) 'Abstract' modifier is not allowed here #loc
      line 37   ERROR         (Annotator) Method should either have body or be abstract #loc
      line 37   WARNING       (Method name is not following coding convention) Method name <code>bad_method</code> doesn't match regex '[a-z][A-Za-z\d]*' #loc
      line 41   ERROR         (Undefined variable) Undefined variable '$argument'
      line 41   WARNING       (Missing or empty conditionals group statement) [EA] Statement has empty block.
      line 41   WARNING       (Statement has empty body) Statement has empty body
      line 41   WARNING       (Type unsafe comparison) [EA] Safely use '===' here.
      line 45   WARNING       (Expression result unused) Expression result is not used anywhere
      line 45   WEAK WARNING  ('isset(...)' usage) [EA] '$argument !== null' construction should be used instead.
      line 48   ERROR         (Annotator) A void function must not return a value #loc
      line 57   ERROR         (Undefined class) Undefined class 'InvalidData'
      line 59   ERROR         (Unused private method) Unused private method 'unused'
      line 59   WARNING       (unused declaration) <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 59   WEAK WARNING  (Missing return type declaration) Missing function's return type declaration
      line 59   WEAK WARNING  (Return type hint can be used) [EA] ': int' can be declared as return type hint.
      line 66   WARNING       (Missing @throws tag(s)) PHPDoc comment doesn't contain all the necessary @throws tags
      line 68   WARNING       (Missing @throws tag(s)) PHPDoc comment doesn't contain all the necessary @throws tags
      line 69   WARNING       (Language level) Union types are only allowed since PHP 8.0
      line 69   WARNING       (unused declaration) <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 73   WEAK WARNING  ('isset(...)' usage) [EA] '$arg !== null' construction should be used instead.
      line 76   WARNING       (Unused local variable) Unused local variable 'item'. The value of the variable is not used anywhere.
      line 76   WEAK WARNING  (Array is always empty at the point of access) Array is always empty at this point
      line 79   WEAK WARNING  (Unhandled exception) Unhandled exceptions
      line 82   WARNING       (Unnecessary semicolon) Unnecessary ;
      line 82   WEAK WARNING  (Unnecessary semicolon) [EA] Unnecessary semicolon.
      line 85   ERROR         (Invalid argument supplied for 'foreach()') Invalid argument supplied to 'foreach'
      line 89   WARNING       (Inconsistent return points) Missing 'return' statement
      line 95   ERROR         (Annotator) Another definition with same name exists in this file #loc
      line 95   ERROR         (Annotator) Class should not extend itself #loc
      line 95   WEAK WARNING  (Empty class) [EA] Class does not contain any properties or methods.
      line 95   WEAK WARNING  (Multiple class declarations) Multiple definitions exist for class 'InspectionViolator'
    """
