Feature: Run inspections with certain severities ignored

  @issue-8 @positive @createsProject
  Scenario: Run inspections on a project with severities ignored in the configuration file
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git
    And I create a configuration file with:
    """
    {
      "ignore-severities": [
        "TYPO",
        "ERROR"
      ],
      "docker-tag": "2021.1.2"
    }
    """
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    24 problems were found during phpStorm inspection.

    Problems in InspectionViolator.php:
      line 10   WARNING       (Constant name is not following coding convention): Constant name <code>badConstant</code> doesn't match regex '[A-Z][A-Z_\d]*' #loc
      line 10   WARNING       (Missing visibility): PSR-12: Missing visibility definition
      line 10   WARNING       (unused declaration): Constant is never used.
      line 16   WARNING       (Language level): Typed properties are only allowed since PHP 7.4
      line 16   WARNING       (Missing PHPDoc comment): Missing PHPDoc comment for field
      line 16   WARNING       (Property name is not following coding convention): Property name <code>$bad_propertie</code> doesn't match regex '[a-z][A-Za-z\d]*' #loc
      line 19   WARNING       (unused declaration): Constructor is never used.
      line 22   WARNING       (Parameters number mismatch declaration): Method call is provided 1 parameters, but the method signature uses 0 parameters
      line 34   WARNING       (PHPDoc comment matches function/method signature): PHPDoc for non-existing argument
      line 35   WARNING       (PHPDoc comment matches function/method signature): Return type does not match the declared
      line 37   WARNING       (Method name is not following coding convention): Method name <code>bad_method</code> doesn't match regex '[a-z][A-Za-z\d]*' #loc
      line 41   WARNING       (Statement has empty body): Statement has empty body
      line 45   WARNING       (Expression result unused): Expression result is not used anywhere
      line 59   WARNING       (unused declaration): <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 59   WEAK WARNING  (Missing return type declaration): Missing function's return type declaration
      line 66   WARNING       (Missing @throws tag(s)): PHPDoc comment doesn't contain all the necessary @throws tags
      line 68   WARNING       (Missing @throws tag(s)): PHPDoc comment doesn't contain all the necessary @throws tags
      line 69   WARNING       (unused declaration): <ul><li>Method owner class is never instantiated OR</li><li>An instantiation is not reachable from entry points.</li></ul>
      line 76   WARNING       (Unused local variable): Unused local variable 'item'. The value of the variable is not used anywhere.
      line 76   WEAK WARNING  (Array is always empty at the point of access): Array is always empty at this point
      line 79   WEAK WARNING  (Unhandled exception): Unhandled exceptions
      line 82   WARNING       (Unnecessary semicolon): Unnecessary ;
      line 89   WARNING       (Inconsistent return points): Missing 'return' statement
      line 95   WEAK WARNING  (Multiple class declarations): Multiple definitions exist for class 'InspectionViolator'
    """

  @issue-8 @negative @createsProject
  Scenario Outline: Run inspections on a project with invalid ignored severities
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git
    And I create a configuration file with:
    """
    {
      "ignore-severities": <payload>
    }
    """
    When I run inspections
    Then the exit code should be 2
    And the last lines of the output should be:
    """
    <message>
    """

    Examples:
      | payload | message                                                                                                                         |
      | ["CAT"] | Invalid values for ignored severities. The allowed values are: TYPO, WEAK WARNING, WARNING, ERROR, SERVER PROBLEM, INFORMATION. |
      | 5       | ignore-severities in the configuration file must be an array.                                                                   |
