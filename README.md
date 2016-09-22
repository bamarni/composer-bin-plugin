# composer-bin-plugin [![Build Status](https://travis-ci.org/bamarni/composer-bin-plugin.svg?branch=master)](https://travis-ci.org/bamarni/composer-bin-plugin)

Isolated vendor for your bin dependencies.


## Why?

For various reasons, you may want to install dependencies that are completely isolated from
the rest of your project. For example if you wish to install two static analysis tools such
as [deptrac](https://github.com/sensiolabs-de/deptrac) and 
[deprecation-detector](https://packagist.org/packages/sensiolabs-de/deprecation-detector)
which have dependencies that conflicts with each other and can conflict with your project,
this tool would allow you to do so.


## How does this plugin work?

It allows you to install your bin vendors in isolated locations, and still link them
to your [bin-dir](https://getcomposer.org/doc/06-config.md#bin-dir).

This is done by registering a `bin` command, which can be used to run Composer commands inside a namespace.


## Installation

    $ composer global require bamarni/composer-bin-plugin:^1.0.0@dev


## Usage

    $ composer bin [namespace] [composer_command]
    $ composer global bin [namespace] [composer_command]


### Example

Let's install Behat and PHPSpec inside a `bdd` namespace :

    $ composer bin deptrac require sensiolabs-de/deptrac
    $ composer bin deptrac require sensiolabs-de/deprecation-detector

This command creates the following directory structure :

    ├── composer.json
    ├── composer.lock
    ├── vendor
    │   └── bin
    │       ├── deptrac -> ../../vendor-bin/bdd/vendor/sensiolabs-de/deptrac/bin/deptrac
    │       └── deprecation-detector -> ../../vendor-bin/bdd/vendor/sensiolabs-de/deprecation-detector/bin/deprecation-detector
    └── vendor-bin
        └── deptrac
        │   ├── composer.json
        │   ├── composer.lock
        │   └── vendor
        └── deprecation-detector
            ├── composer.json
            ├── composer.lock
            └── vendor


You can continue to run `./vendor/bin/deptrac` and `./vendor/bin/deprecation-detector`,
but they'll use an isolated set of dependencies. In case of conflicts of binaries, none
are symlinked.


### The "all" namespace

The "all" namespace has a special meaning. It runs a command for
all existing namespaces.

For instance, the following command would update all your bins :

    > composer bin all update
    Changed current directory to vendor-bin/phpspec
    Loading composer repositories with package information
    Updating dependencies (including require-dev)
    Nothing to install or update
    Generating autoload files
    Changed current directory to vendor-bin/phpunit
    Loading composer repositories with package information
    Updating dependencies (including require-dev)
    Nothing to install or update
    Generating autoload files


## Tips


### Auto-installation

For convenience, you can add the following script in your `composer.json` :

```json
{
    "scripts": {
        "post-install-cmd": ["@composer bin all install --ansi"],
        "post-update-cmd": ["@composer bin all update --ansi"]
    }
}
```

This makes sure all your bins are installed during `composer install` and updated during `composer update`.


### Disable links

By default, binaries of the sub namespaces are linked to the root one like described in [example](#example). If you
wish to disable that behaviour, you can do so by adding a little setting in the extra config:

```json
{
    "extra": {
        "bamarni-bin": {
            "bin-links": false
        }
    }
}
```


## License

This project has been released under the [MIT License](LICENSE).
