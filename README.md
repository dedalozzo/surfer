[![Latest Stable Version](https://poser.pugx.org/3f/surfer/v/stable.png)](https://packagist.org/packages/3f/surfer)
[![Latest Unstable Version](https://poser.pugx.org/3f/surfer/v/unstable.png)](https://packagist.org/packages/3f/surfer)
[![Build Status](https://scrutinizer-ci.com/g/dedalozzo/surfer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/dedalozzo/surfer/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dedalozzo/surfer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dedalozzo/surfer/?branch=master)
[![License](https://poser.pugx.org/3f/surfer/license.svg)](https://packagist.org/packages/3f/surfer)
[![Total Downloads](https://poser.pugx.org/3f/surfer/downloads.png)](https://packagist.org/packages/3f/surfer)


Surfer
======
An HTTP client that uses cURL or standard sockets and handle chunk responses.


Composer Installation
---------------------

To install Surfer, you first need to install [Composer](http://getcomposer.org/), a Package Manager for
PHP, following those few [steps](http://getcomposer.org/doc/00-intro.md#installation-nix):

```sh
curl -s https://getcomposer.org/installer | php
```

You can run this command to easily access composer from anywhere on your system:

```sh
sudo mv composer.phar /usr/local/bin/composer
```


Surfer Installation
-------------------
Once you have installed Composer, it's easy install Surfer.

1. Edit your `composer.json` file, adding Surfer to the require section:
```sh
{
    "require": {
        "3f/surfer": "dev-master"
    },
}
```
2. Run the following command in your project root dir:
```sh
composer update
```


Documentation
-------------
The documentation can be generated using [Doxygen](http://doxygen.org). A `Doxyfile` is provided for your convenience.


Requirements
------------
- PHP 5.6.0 or above.


Authors
-------
Filippo F. Fadda - <filippo.fadda@programmazione.it> - <http://www.linkedin.com/in/filippofadda>


License
-------
This project is licensed under the Apache License, Version 2.0 - see the LICENSE file for details.