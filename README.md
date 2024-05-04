# Labrador HTTP CORS

[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http-cors.svg?style=flat-square)](https://github.com/labrador-kennel/http-cors/releases/latest)
[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http-cors.svg?style=flat-square)](http://opensource.org/licenses/MIT)

A PHP library intended to provide spec-compliant CORS middleware for projects running on [Amp's http-server](https://amphp.org/http-server/). 
Though this library lives under the Labrador namespace it has only one dependency, `amphp/http-server`, and does not depend 
on any other Labrador packages.

## Installation

[Composer](https://getcomposer.org) is the only supported method for installing Labrador packages.

```
composer require cspray/labrador-http-cors
```

## Documentation

Labrador packages have thorough documentation in-repo in the `docs/` directory. You can also check out the documentation 
online at [https://labrador-kennel.io/docs/http-cors](https://labrador-kennel.io/docs/http-cors).

## Notice about versions

This library is compatible with all versions of amphp http server. But depending on the version of PHP you are using or
the version of amphp, you will need to pick a specific one. Learn more in the following version comparative:

| PHP  | amphp | labrador-http-cors |
|------|-------|--------------------|
| 8.1+ | 3     | 0.4                |
| 7.1+ | 2     | 0.1, 0.2, 0.3      |

## Governance

All Labrador packages adhere to the rules laid out in the [Labrador Governance repo](https://github.com/labrador-kennel/governance).

