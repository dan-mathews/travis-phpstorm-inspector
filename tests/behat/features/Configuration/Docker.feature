Feature: Specify docker images in configuration

  @issue-14 @negative
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
    Then the exit code should be 1
    And the last lines of the output should be:
    """
    <message>
    """

    Examples:
      | dockerConfig                                      | message                                              |
      | "docker_tag": "cat"                               | Could not pull docker image danmathews1/phpstorm:cat |
      | "docker_repository": "cat"                        | Could not pull docker image cat:latest               |
      | "docker_tag": "1.0.0", "docker_repository": "cat" | Could not pull docker image cat:1.0.0                |
