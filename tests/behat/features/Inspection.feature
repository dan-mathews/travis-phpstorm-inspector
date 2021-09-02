Feature: Run inspections

  @issue-1 #@createsProject see issue-4
  Scenario: Use an inspections file with the wrong extension
    Given I create a new project
    And I initialise git
    And I create an invalid inspections xml file
    And I create a php file without problems
    And I stage the php file in git
    And I am expecting an error
    And I pull docker image '1.0.0-phpstorm2021.1.2'
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    The inspections profile at /app/invalid.txt does not have an xml extension.
    """
