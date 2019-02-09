# Changelog

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