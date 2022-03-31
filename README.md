[![Build Status](https://travis-ci.com/dan-mathews/travis-phpstorm-inspector.svg?branch=master)](https://travis-ci.com/coderman17/typed-arrays)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/dan-mathews/travis-phpstorm-inspector/blob/master/.travis/standards)
[![Psalm Level](https://img.shields.io/badge/Psalm-max-brightgreen.svg?style=flat)](https://github.com/dan-mathews/travis-phpstorm-inspector/blob/master/psalm.xml)
[![CodeSniffer Level](https://img.shields.io/badge/CodeSniffer-PSR12-brightgreen.svg?style=flat)](https://github.com/dan-mathews/travis-phpstorm-inspector/blob/master/.travis/standards)

# Travis PhpStorm Inspector
### Summary
Travis PhpStorm Inspector is a tool for conveniently adding PhpStorm's code inspections to your CI pipeline, to ensure
that all the code merged into your projects meets your quality standards.

### Motivation
PhpStorm offers a valid and unique set of inspections to improve the quality of your code, but until now the inspections
have required:
1. The presence of an `.idea` directory, which you probably don't want to add to your codebase as:
   1. It's IDE specific
   2. It's easily changed by accident
   3. It contains non-essential configuration, relating to your workspace etc.
2. The ability to orchestrate and run PhpStorm itself in a CI context, which is a challenge because:
   1. It's a large IDE with its own significant dependencies
   2. Its features, such as inspections, weren't originally designed to be used outside of the context of a PhpStorm project

### Solutions
Travis PhpStorm Inspector solves these problems by:
1. Conveniently generating the `.idea` directory from sensible defaults, commandline options, or a single configuration
file (just like PhpStan's `phpstan.neon`, or Psalm's `psalm.xml`).
```diff
+ This means that you can reliably leverage PhpStorm's inspections for stronger coding standards,
+ without muddling your codebase with a bulky, IDE-specific directory.
```
2. Using a variety of docker containers, available for download on dockerhub, to house the PhpStorm IDE for you. You
can also create custom docker containers to contain your specific PhpStorm plugins by forking and tweaking the Dockerfiles  
``` diff
+ This means that you can use PhpStorm as if it was a lightweight inspection tool.
```

### Additional Benefits From Local Use
The Travis PhpStorm Inspector also runs locally, which brings a number of great benefits:
1. It removes your team's dependency on PhpStorm for Development, allowing them to check their code against your coding
standards whilst using whichever IDE they're most comfortable with
2. It removes concerns that updating PhpStorm may change the way inspections work, allowing your team to stay fully up-to-date,
safe in the knowledge that your inspections remain consistent
4. It removes the need to deal with PhpStorm's many quirks when it comes to PhpStorm's inspections, allowing you
to give new or less experienced PhpStorm users a simple and pre-configured way to run inspections

## Installation for use with Travis
Here is an example script `.travis/inspections` which requires tweaking where indicated:
```
#!/bin/sh
set -e
git branch tmp
git fetch `git config --get remote.origin.url` $TRAVIS_BRANCH
git checkout FETCH_HEAD
git merge --no-commit --no-ff tmp

# Do whatever setup is necessary for your project here (in this example, it's just an install):
composer install

# Pull the image you reference in your configuration file or command line arguments here:
docker pull danmathews1/phpstorm_ea-extended:latest

# Clone the repository containing your inspections xml file here (in this example, an 'rfc' repository is referenced):
git clone https://github.com/MY_USERNAME/rfc.git ~/rfc

# Here we clone and install this project:
git clone https://github.com/dan-mathews/travis-phpstorm-inspector.git ~/travis-phpstorm-inspector
composer install --working-dir=/home/travis/travis-phpstorm-inspector

# Here we run the inspection itself, referencing the project root, the xml configuration, and the travis-phpstorm-inspector configuration
~/travis-phpstorm-inspector/bin/inspector inspect ~/build/MY_USERNAME/MY_PROJECT --profile ~/rfc/resources/phpstorm/inspections.xml --configuration ~/build/MY_USERNAME/MY_PROJECT/config/travis-phpstorm-inspector/travis-phpstorm-inspector.json -vvv
```

Here is an example job config within `travis.yml` to trigger the script above:
```
jobs:
  include:
    - stage: test
      name: "PhpStorm Inspections"
      language: php
      php:
        - '7.3'
      script: ./.travis/inspections
```

## Installation for use locally
- Clone the repository and install dependencies via [Composer](https://getcomposer.org/) with:
```shell
composer install
```
- Pull the docker image you wish to use, here's the default one:
```shell
docker pull danmathews1/phpstorm:latest
```
- Create a travis-phpstorm-inspector.json like this to exclude your non-project folders, and place it in your project root:
``` json
{
  "exclude-folders": [
    "vendor",
    ".travis",
    "bin"
  ]
}
```
- Run the following command to start your inspection:
```
bin/inspector inspect path/to/project
```

## Troubleshooting
- You might find you need the mb-string extension, installable with: `sudo apt install php-mbstring`
