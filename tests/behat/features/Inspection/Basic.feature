Feature: Run inspections

  Background:
    Given I create a new project
    And I initialise git

  @issue-1 @negative @createsProject
  Scenario: Use an inspections file with the wrong extension
    Given I create an invalid inspections xml file
    And I create a php file without problems
    And I stage the php file in git
    And I am expecting an error
    When I run inspections
    Then the exit code should be 2
    And the last lines of the output should be:
    """
    The inspections profile invalid.txt does not have an xml extension.
    """
