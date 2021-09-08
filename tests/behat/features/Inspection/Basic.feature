Feature: Run inspections

  @issue-1 @negative
  Scenario: Use an inspections file with the wrong extension
    Given I create a new project
    And I initialise git
    And I create an invalid inspections xml file
    And I create a php file without problems
    And I stage the php file in git
    And I am expecting an error
    And I create a configuration file with:
    """
    {
      "docker_tag": "2021.1.2"
    }
    """
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    The inspections profile at invalid.txt does not have an xml extension.
    """
