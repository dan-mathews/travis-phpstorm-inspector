Feature: Run inspections with a configuration file

  @issue-8 @negative @createsProject
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
    Then the exit code should be 2
    And the last lines of the output should be:
    """
    Could not process the configuration file as json.
    """

  @issue-21 @positive
  Scenario: Run the inspections command with help option
    When I run inspections with help option
    Then the exit code should be 0
    And the last lines of the output should be:
    """
    Usage:
      inspect [options] [--] [<project-path>]

    Arguments:
      project-path                                 The absolute or relative path of the project to inspect
                                                   - default: the current working directory

    Options:
          --profile[=PROFILE]                      The absolute or relative path of the inspection profile to use
                                                   - default: PhpStorm's default profile, see /data/default.xml
          --ignore-severities[=IGNORE-SEVERITIES]  The severities to ignore, as a comma-separated list without spaces e.g. 'TYPO','INFORMATION'
                                                   - default: ''
          --docker-repository[=DOCKER-REPOSITORY]  The name of the docker repository to use, containing a PhpStorm image
                                                   - default: danmathews1/phpstorm
          --docker-tag[=DOCKER-TAG]                The docker tag to use, referencing a PhpStorm image in the docker repository
                                                   - default: latest
      -h, --help                                   Display help for the given command. When no command is given display help for the list command
      -q, --quiet                                  Do not output any message
      -V, --version                                Display this application version
          --ansi|--no-ansi                         Force (or disable --no-ansi) ANSI output
      -n, --no-interaction                         Do not ask any interactive question
      -v|vv|vvv, --verbose                         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    """
