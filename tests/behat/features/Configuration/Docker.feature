Feature: Specify docker images in configuration

  @issue-14 @negative @createsProject
  Scenario: Use configuration and defaults to specify images which don't exist on dockerhub
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git
    And I create a configuration file with:
    """
    {
      "docker-tag": "1.0.0",
      "docker-repository": "cat"
    }
    """
    When I run inspections
    Then the exit code should be 2
    And the last lines of the output should be:
    """
    Docker image 'cat:1.0.0' doesn't seem to exist locally.
    If you would like to pull it, use this command:

      docker pull cat:1.0.0
    """
