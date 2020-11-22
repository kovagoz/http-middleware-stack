# PSR-15 compatible HTTP middleware stack

## Requirements

- PHP >=7.4 (sorry, but I like typed properties)

## Usage

You must specify a default response which returns if there is no
middleware in the stack or none of them responded.

```php
$stack = new \Kovagoz\Http\MiddlewareStack($defaultResponse);
$stack->push(new InnerMiddleware());
$stack->push(new OuterMiddleware());

$response = $stack->handle($serverRequest);
```
