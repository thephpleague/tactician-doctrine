# tactician-doctrine

[![Latest Version](https://img.shields.io/github/release/thephpleague/tactician-doctrine.svg?style=flat-square)](https://github.com/thephpleague/tactician-doctrine/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/thephpleague/tactician-doctrine/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/tactician-doctrine)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/tactician-doctrine.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/tactician-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/league/tactician-doctrine.svg?style=flat-square)](https://packagist.org/packages/league/tactician-doctrine)

This package adds plugins for using Tactician with Doctrine components, either the ORM or just DBAL. The main feature is the ability to wrap each command in a separate database transaction.

## Setup

Via Composer

``` bash
$ composer require league/tactician-doctrine
```

Next, add the `ORM\TransactionMiddleware` to your CommandBus:

``` php
$commandBus = new \League\Tactician\CommandBus(
    [
        new TransactionMiddleware($ormEntityManager)
    ]
);
```

That's it. Each command you execute will now open and close a new transaction. 

If a command fires off more commands, be aware that those commands will run in the same transaction as the parent. It is recommended that you run each command as a separate transaction, so to prevent this from happening, use the [`LockingMiddleware` that ships in Tactician core](http://tactician.thephpleague.com/plugins/locking-middleware/). This will queue the commands up internally until the parent command has completed.

If an exception is raised while handling the command, the transaction is rolled back, the EntityManager closed, and the exception rethrown.

## Symfony2 integration
When using the [tactician-bundle] (https://github.com/thephpleague/tactician-bundle), don't forget to add the Doctrine middleware to your Symfony config:

```
tactician:
    commandbus:
        default:
            middleware:
             - tactician.middleware.locking
             - tactician.middleware.doctrine
             - tactician.middleware.command_handler
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Security
Disclosure information can be found on [the main Tactician repo](https://github.com/thephpleague/tactician#security).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
