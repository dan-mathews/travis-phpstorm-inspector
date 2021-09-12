Feature: Run inspections with a configuration file

  @issue-8 @negative
  Scenario: Run inspections on a project with invalid configuration file
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git
    And I create a configuration file with:
    """
    'cat'
    """
    When I run inspections
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    Could not process the configuration file as json.
    """
