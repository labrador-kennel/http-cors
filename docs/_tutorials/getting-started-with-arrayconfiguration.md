---
title: Getting Started with ArrayConfiguration
---
Start off using CORS Middleware with a CORS Configuration represented by an array. If you already have a CORS
configuration represented as an array or can't store your configuration as PHP code this might be the implementation
for you.

Using `ArrayConfiguration` is as simple as providing an array to the constructor with all of your configuration values.

<script src="https://gist.github.com/cspray/df6d0f8c5565b591fa6db0c8f62e789d.js"></script>

The only key required is `origins`, otherwise the corresponding CORS header will not be set if 
there are no values provided. Once you have the `CorsMiddleware` instantiated you can attach it to your http-server 
router as appropriate for your application stack. If you are running [Labrador HTTP] you would attach this to the 
`Application` directly if you'd like to handle CORS requests similarly for all routes. You also have the option to 
attach this Middleware to a specific route or group of routes.

<div class="message is-info">
    <div class="message-body">
        This implementation does not make stringent type checks against values passed to constructor. If the values 
        provided are not a correct type you will encounter runtime errors when a request is processed. If type safety 
        is important to you please check <a href="./tutorials/getting-started-with-configurationbuilder">Getting Started with ConfigurationBuilder</a>.
    </div>
</div>

[Labrador HTTP]: https://github.com/labrador-kennel/http