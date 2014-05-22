# KrakenBundle

This bundle allows you to integrate [kraken.io](https://kraken.io/) into your Symfony2 application.

## Install:

1. Add KrakenBundle to your dependencies:

        // composer.json
        {
            // ...
            "require": {
                // ...
                "pompdelux/kraken-bundle": "1.x"
            }
        }
2. Use Composer to download and install the bundle:

        $ php composer.phar update pompdelux/kraken-bundle
3. Register the bundle in your application:

        // app/AppKernel.php
        class AppKernel extends Kernel
        {
            // ...
            public function registerBundles()
            {
                $bundles = array(
                    // ...
                    new Pompdelux\KrakenBundle\KrakenBundle()
                );
            }
        }

4. Add the configuration needed to use the bundle:

        // config.yml
        kraken:
            services:
                service_name:
                    api_key:    your-kraken.io-key
                    api_secret: your-kraken.io-secret

## Usage:

### Basic example:

```php
$kraken = $container->get('pompdelux.kraken.service_name');
$result = $kraken->squeeze('http://example.com/some/public/image.jpg');
```

### Resize image

```php
$sizes = [
    [
        'height' => 200,
        'width' => 400,
    ], [
        'height' => 100,
        'width' => 200,
    ],
];

$kraken = $container->get('pompdelux.kraken.service_name');
$results = $kraken->resize('http://example.com/some/public/image.jpg', $sizes);
```

### Example with callback rather than wait strategy:

```yml
# config.yml
kraken:
    services:
        ...
        callback_service:
            api_key:        your-kraken.io-key
            api_secret:     your-kraken.io-secret
            callback:       true
            callback_route: your_callback_route

# routing.yml
acme_kraken_callback:
    pattern: /my/kraken/callback
    defaults: { _controller: AcmeTestBundle:Kraken:callback }
    requirements:
        _method:  POST

```

```php
$kraken = $this->container->get('pompdelux.kraken.callback_service');
$result = $kraken->squeeze('http://example.com/some/public/image.jpg');

// In AcmeTestBundle/Controller/KrakenController.php
//
// this method will be called once kraken.io is done processing your image.
public function callbackAction(Request $request)
{
    error_log(print_r($request->getContent(), 1));
    return new Response();
}
```

## BCCResqueBundle

The bundle also provides a worker for handeling kraken processing via [BCCResqueBundle](https://github.com/michelsalib/BCCResqueBundle)

Jobs will be added to the `kraken` resque queue.

Basic usage:

```php
use Pompdelux\KrakenBundle\Worker\BCCResqueWorker;

$resque = $container->get('bcc_resque.resque');

// process one file
$job = new BCCResqueWorker('pompdelux.kraken.service_name', 'http://example.com/public/path/to/file.jps', '/target/dir/');
$resque->enqueue($job);

// process one file, delete original
$job = new BCCResqueWorker('pompdelux.kraken.service_name', 'http://example.com/public/path/to/file.jps', '/target/dir/', true);
$resque->enqueue($job);


// scale one file, delete original
$sizes = [
    0 =>  [
        'width'    => 200,
        'height'   => 200,
        'strategy' => 'auto'
    ]
];

$job = new BCCResqueWorker('pompdelux.kraken.kraken_service_name', 'http://example.com/public/path/to/file.jps', '/target/dir/', true, $sizes);
$resque->enqueue($job);
```
