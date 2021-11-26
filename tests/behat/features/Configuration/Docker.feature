Feature: Specify docker images in configuration

  @issue-14 @negative @createsProject
  Scenario Outline: Use configuration and defaults to specify images which don't exist on dockerhub
    Given I create a new project
    And I initialise git
    And I create a valid inspections xml file
    And I create a php file with problems
    And I stage the php file in git
    And I create a configuration file with:
    """
    {
      <dockerConfig>
    }
    """
    When I run inspections
    Then the exit code should be 2
    And the last lines of the output should be:
    """
    <message>
    """

    Examples:
      | dockerConfig                                      | message                                                                |
      | "docker-tag": "cat"                               | Docker image 'danmathews1/phpstorm:cat' doesn't seem to exist locally. |
      | "docker-repository": "cat"                        | Docker image 'cat:latest' doesn't seem to exist locally.               |
      | "docker-tag": "1.0.0", "docker-repository": "cat" | Docker image 'cat:1.0.0' doesn't seem to exist locally.                |
