# Multiple Mongo Instance Monitoring tool



Tool for live monitoring of multiple mongodb instances

## Monitoring tool

### Installation

The recommended way to install Monitoring tool is with the [Composer](http://getcomposer.org/) package manager. (See the Composer installation guide for information on installing and using Composer.)

Run the following command to install dependencies in root:

`composer install`

### Configuration

Monitoring tool is configured via `config.neon` file in root of this app. See `config.neon.default` for more detailed information.

```
hosts:
    - 127.0.0.01:27027
```

There are currently supported just mongo databases without authorization.

### Running monitoring tool

Run server `php src/run.php`


## Browser interfac

### Installation

Inside `gui/` directoru run `npm install`

### Running

To run application run command `npm start` and follow instructions on console (visit localhost:5000 to see gui)
There could be specified `?host=localhost&port=9900` setting, when host or port for monitoring server tool is different than default `localhost:9900`


