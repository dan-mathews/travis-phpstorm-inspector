Feature: Run inspections with certain folders excluded

  Background:
    Given I create a new project
    And I pull docker image 'danmathews1/phpstorm:2021.1.2'
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git

  @issue-39 @positive @createsProject
  Scenario: Run inspections on a project with a folder excluded
    Given I create a configuration file with:
    """
    {
      "exclude-folders": [
        "src"
      ],
      "docker-tag": "2021.1.2"
    }
    """
    When I run inspections
    Then the exit code should be 0
    And the last lines of the output should be:
    """
    No problems to report.
    """
