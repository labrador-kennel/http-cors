# Changelog

## 0.3.0 - 2020-??-??

**This release introduces breaking changes. Please review entries carefully when upgrading from a previous release.**

#### Added

- Added a `ConfigurationBuilder` to easily build a Configuration object using a type-safe mechanism as 
opposed to the ArrayConfiguration instance.

#### Changed

- Changed the `Configuration::getMaxAge()` method to return `?int` because sometimes this value does 
not need to be explicitly set and using the browser default is preferred.
- Updates the ArrayConfiguration instance to handle when a key is not present and ensures that required 
values are passed in the constructor.
- Renamed `Configuration::getOrigin()` -> `Configuration::getOrigins()` and it now expects an array of 
string instead of just one. The thought process being that the rules that apply to 1 Origin is likely to 
apply to another.


## 0.2.1 - 2020-04-09

#### Fixed

- Fixes a bug where a wildcard origin `*` was not being properly respected in some 
circumstances.

## 0.2.0 - 2020-03-27

#### Changed

- Updated dependencies to allow for Amp http-server 2.0+
- Other administrative tasks related to maintenance of the codebase

## 0.1.0 - 2019-02-09

#### Added

- Adds a `Configuration` interface defining the data necessary to respond to CORS requests 
correctly.
- Adds an implementation `ArrayConfiguration` that allows passing an associative array 
to define data for the Middleware.
- Adds a `CorsMiddleware` implementation that responds to cross-origin requests with the 
appropriate headers. This supports:
    - Setting the Vary header to include 'Origin'; this respects any existing header you 
    may have already set yourself.
    - Allows you to send credentials with CORS requests for setting/reading authentication cookies.
    - Responds with appropriate status codes; `403` if the Origin is invalid or the CORS 
    request headers include a value that is not allowed. `405` if the CORS request method is 
    not allowed. And finally `200` in all other scenarios.