PHP Ratelimiter
===============

A framework independent, flexible and highly extensible rate limiter for PHP.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/51be0137-1158-403a-9fc7-ab863f2c0ca9/mini.png)](https://insight.sensiolabs.com/projects/51be0137-1158-403a-9fc7-ab863f2c0ca9)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunspikes/php-ratelimiter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunspikes/php-ratelimiter/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunspikes/php-ratelimiter/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunspikes/php-ratelimiter/?branch=master)
[![Code Climate](https://codeclimate.com/github/sunspikes/php-ratelimiter/badges/gpa.svg)](https://codeclimate.com/github/sunspikes/php-ratelimiter)
[![Build Status](https://travis-ci.org/sunspikes/php-ratelimiter.svg?branch=master)](https://travis-ci.org/sunspikes/php-ratelimiter)
[![Latest Stable Version](https://poser.pugx.org/sunspikes/php-ratelimiter/v/stable)](https://packagist.org/packages/sunspikes/php-ratelimiter)
[![License](https://poser.pugx.org/sunspikes/php-ratelimiter/license)](https://packagist.org/packages/sunspikes/php-ratelimiter)

## Installation

### With Composer

It is best installed it through [packagist](http://packagist.org/packages/sunspikes/php-ratelimiter) 
by including `sunspikes/php-ratelimiter` in your project composer.json require:

``` json
    "require": {
        "sunspikes/php-ratelimiter":  "dev-master"
    }
```

### Without Composer

You can also download it from [Github] (https://github.com/sunspikes/php-ratelimiter), 
but no autoloader is provided so you'll need to register it with your own PSR-4 
compatible autoloader.

## Usage

### Overview

```php
// 1. Make a rate limiter with limit 3 attempts in 10 minutes
$cacheAdapter = new DesarrollaCacheAdapter((new DesarrollaCacheFactory())->make());
$settings = new FixedWindowSettings(3, 600);
$ratelimiter = new RateLimiter(new ThrottlerFactory($cacheAdapter), new HydratorFactory(), $settings);

// 2. Get a throttler for path /login 
$loginThrottler = $ratelimiter->get('/login');

// 3. Register a hit
$loginThrottler->hit()

// 4. Check if it reached the limit
if ($loginThrottler->check()) {
    // access permitted
} else {
    // access denied
}

// Or combine the steps 3 & 4
if ($loginThrottler->access()) {
    // access permitted
} else {
    // access denied
}

// To get the number of hits
print $loginThrottler->count(); // or count($throttler)
```

### Configuration

By default PHP Ratelimiter uses the [desarolla2 cache adapter](https://github.com/desarrolla2/Cache), the sample configuration provided in ```config/config.php```

You can configure the drivers in ```config.php```, for example to use memcache change the driver to ```'memcache'```

```php
return [
    'default_ttl' => 3600,
    'driver'      => 'memcache',
    'memcache' => [
        //....
    ],
];
```

### Extending

The PHP Ratelimiter is highly extensible, you can have custom adapters by implementing ```Sunspikes\Ratelimit\Cache\Adapter\CacheAdapterInterface``` 

For example to use Doctrine cache adapter

```php
class DoctrineCacheAdapter implements CacheAdapterInterface
{
    public function __construct($cache)
    {
        $this->cache = $cache;
    }
    
    // Implement the methods
}

// Build adapter using APC cache driver
$adapter = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ApcCache());
```

Also you can have custom hydrators by implementing ```Sunspikes\Ratelimit\Throttle\Hydrator\DataHydratorInterface```

For example to use a Symfony Request object instead of custom URL for ratelimiting

```php
class RequestHydrator implements DataHydratorInterface
{
    public function hydrate($data, $limit, $ttl)
    {
        // Make the key string
        $key = $data->getClientIp() . $data->getPathInfo();

        return new Data($key, $limit, $ttl);
    }
}

// Hydrate the request to Data object
$hydrator = new RequestHydrator();
```

Then decorate or extend the HydratorFactory to recognize your data

```php
use Hydrator\FactoryInterface;

class MyHydratorFactory implements FactoryInterface
{
    private $defaultFactory;

    public function __construct(FactoryInterface $defaultFactory)
    {
        $this->defaultFactory = $defaultFactory;
    }

    public function make($data)
    {
        if ($data instanceof Request) {
            return new RequestHydrator();
        }

        return $this->defaultFactory->make($data);
    }
}
```

### Throttler types

#### Fixed Window
A fixed window throttler will allow X requests in time Y. Any further access attempts will be counted, but return false as status. See [Overview example](#overview) for instantiation.

__Note: time limit is in seconds__

#### Leaky Bucket
A [leaky bucket](https://en.wikipedia.org/wiki/Leaky_bucket) throttler will allow X requests divided over time Y.

Any access attempts past the threshold T (default: 0) will be delayed by ![equation](http://www.sciweavers.org/tex2img.php?eq=%5Cfrac%7BY%7D%7BX-T%7D%20&bc=White&fc=Black&im=jpg&fs=12&ff=arev&edit=0).

`access()` will return false if delayed, `hit()` will return the number of milliseconds waited

__Note: time limit is in milliseconds__

```php
// Make a rate limiter with limit 120 attempts per minute, start delaying after 30 requests
$settings = new LeakyBucketSettings(120, 60000, 30);

$cacheAdapter = new DesarrollaCacheAdapter((new DesarrollaCacheFactory())->make());
$timeAdapter = new PhpTimeAdapter();

$throttlerFactory = new BucketThrottlerFactory($cacheAdapter, $timeAdapter);
$hydratorFactory = new HydratorFactory();

$ratelimiter = new RateLimiter($throttlerFactory, $hydratorFactory, $settings);
```

## Author

Krishnaprasad MG [@sunspikes]

## Contributing

Please feel free to send pull requests.

## License

This is an open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
