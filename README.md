# Contributing to this project

Anyone is welcome to contribute code changes and additions to this project. If you'd like your changes merged into the dev branch, please read the following document before opening a [pull request][pulls].

There are several ways in which you can help improve this project:

1. Fix an existing [issue][issues] and submit a [pull request][pulls].
1. Review open [pull requests][pulls].
1. Report a new [issue][issues]. _Only do this after you've made sure the behavior or problem you're observing isn't already documented in an open issue._

## Table of Contents

- [Getting Started](#getting-started)
- [Making Changes](#making-changes)
- [Deploying to Elastic Beanstalk](#deploying-to-elastic-beanstalk)
- [Code Style](#code-style)
- [Legalese](#legalese)
- [FAQ](#faq)

## Getting Started

This project is a [Drupal](https://www.drupal.org/) (version 8.7.x) content management system.
Development dependencies are managed using [Composer](https://getcomposer.org/).

### How is this site laid out?

When installing the given `composer.json` some tasks are taken care of:

- Drupal will be installed in the `docroot`-directory.
- Modules (packages of type `drupal-module`) will be placed in `docroot/modules/contrib/`
- Theme (packages of type `drupal-theme`) will be placed in `docroot/themes/contrib/`
- Profiles (packages of type `drupal-profile`) will be placed in `docroot/profiles/contrib/`
- Latest version of drush is installed locally for use at `docroot/vendor/bin/drush`.
- Latest version of DrupalConsole is installed locally for use at `docroot/vendor/bin/drupal`.
- Creates environment variables based on your .env file. See [.env.example](.env.example).

### Usage

First you need to [install docker](https://docs.docker.com/docker-for-mac/install/).

Clone this repo in your desired directory:

```sh
git clone git@github.com:Bixal/solutioning-exercise.git
cd solutioning-exercise
```

Set default environment variables and connection strings:

```sh
cp .env.example .env
cp docroot/sites/default/settings.local.php.example docroot/sites/default/settings.local.php
```

Initialize the docker containers with Ngnix, Drupal app, and MariaDB:

```sh
docker-compose up -d
```

Install a standard Drupal application:

```sh
make installdrupal
```

Setup the application:

> Note: You don't have to execute this command if you have a db dump file(s) in your mariadb-init folder. In that case execute `make setup`.

To stop the containers execute:

```sh
make stop
```

If you want to know more available commands, please review the following document [Makefile][makefile]

Redirect cmssolution.docker.localhost to your localhost:

```sh
sudo sh -c "echo '127.0.0.1 cmssolution.docker.localhost' >> /etc/hosts"
```

Lastly, navigate to [https://cmssolution.docker.localhost:8000](https://cmssolution.docker.localhost:8000) in your Web browser of choice.

### Database Import

Instead of installing the site, you can choose to place a .sql or .sql.gz file
in mariadb-init. All files in this folder will be imported, in alphabetical order.
.sql and .sql.gz files are gitignored so you do not have to worry about them
getting committed.

## Making Changes

1. Clone the project's repo.
1. Place an updated db dump (.sql or .sql.gz) file in mariadb-init.
1. Create a feature branch for the code changes you're looking to make: `git checkout -b your-descriptive-branch-name origin/master`.
1. _Write some code!_
1. Run the application and verify that your changes function as intended. Remember to run `make cr` if you are not seeing your changes.
1. If your changes would benefit from testing, add the necessary tests and verify everything passes.
1. Export the configuration with your changes: `make cex`.
1. Run tests and coding standards: `make codeck`
1. Commit your changes: `git commit -am 'Add some new feature or fix some issue'`. _(See [this excellent article](https://chris.beams.io/posts/git-commit) for tips on writing useful Git commit messages.)_
1. Push the branch to move.mil repository: `git push -u origin your-descriptive-branch-name`.
1. Wait until al checks are passed.
1. Create a new pull request and we'll review your changes.

### Verifying Changes

```sh
make codeck
```

## FAQ

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull 
request is often a better solution), you can do so with the 
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra 
section of composer.json:

```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```
