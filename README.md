# Multiple Mongo Instance Monitoring tool

Tool for live monitoring of multiple mongodb instances

## Installation

The recommended way to install Monitoring tool is with the [Composer](http://getcomposer.org/) package manager. (See the Composer installation guide for information on installing and using Composer.)

Run the following command to install dependencies in root:

`composer install`

## Configuration

Monitoring tool is configured via `config.neon` file in root of this app. See `config.neon.default` for more detailed information.

```
hosts:
    - 127.0.0.01:27027
```

There are currently supported just mongo databases without authorization.

## Running

1. Run server `php src/run.php`
2. Open file `gui/index.html` in your browser. There could be specified `?host=localhost&port=9900` setting, when host or port is different than default `localhost:9900`

