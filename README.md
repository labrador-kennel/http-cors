# Labrador HTTP CORS

A PHP 7+ library intended to provide spec-compliant CORS middleware for projects running 
on [amphp http-server]. Though this library lives under the Labrador namespace it has only 
one dependency, `amphp/http-server`, and does not depend on any other Labrador packages.

## Installation

We only support installing Labrador packages via [Composer].

```
composer require cspray/labrador-http-cors
```

## Documentation

This package has thorough, in-repo documentation that can be found in the `docs/` directory. You 
can also find documentation online at https://labrador-kennel.io/http-cors. 

The documenation is split into the following sections:

- "Tutorials" (Located in `docs/_tutorials`)
    
    Details how to get started with the library. If you're looking to get started quickly this 
    is probably where you want to be
    
- "How Tos" (Located in `docs/_how-tos`)

    Details how to perform specific tasks that can be completed with the library. Generally these 
    are step by step guides with more explanation on how everything interacts and works together.
    
- "References" (Located in `docs/_references`)

    Details explicitly technical information about the library. This is where highly detailed 
    explanations of the nuts and bolts of the library can be found. Some sufficiently simple 
    libraries may not have reference documentation.

## Governance

All Labrador packages adhere to the rules laid out in the [Labrador Governance repo]

[amphp http-server]: https://amphp.org/http-server/
[Composer]: https://getcomposer.org
[Labrador Governance repo]: https://github.com/labrador-kennel/governance
