# HanzoKrakenBundle

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


