<?php
/*
 * This file is part of the hanzo package.
 *
 * (c) Ulrik Nielsen <un@bellcom.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pompdelux\KrakenBundle;

use Guzzle\Service\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;

class Kraken
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Client
     */
    protected $guzzle;

    /**
     * @var string
     */
    protected $service_type;

    /**
     * @var string
     */
    protected $api_key;
    protected $api_secret;

    /**
     * @var null|string
     */
    protected $callback_route;

    /**
     * @var bool
     */
    protected $use_lossy;


    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @param Router          $router
     * @param string          $api_key
     * @param string          $api_secret
     * @param string          $type
     * @param bool            $use_lossy
     * @param string          $callback_route
     */
    public function __construct(Client $client, LoggerInterface $logger, Router $router, $api_key, $api_secret, $type = 'url', $use_lossy = true, $callback_route = null)
    {
        $this->guzzle         = $client;
        $this->logger         = $logger;
        $this->router         = $router;
        $this->api_key        = $api_key;
        $this->api_secret     = $api_secret;
        $this->service_type   = $type;
        $this->use_lossy      = $use_lossy;
        $this->callback_route = $callback_route;
    }


    /**
     * @param $image
     * @return KrakenResponse
     */
    public function squeeze($image)
    {
        return $this->send($image);
    }


    /**
     * @param  string $image
     * @param  array  $sizes
     * @return array
     * @throws \InvalidArgumentException
     */
    public function resize($image, array $sizes = [])
    {
        // validation first.
        foreach ($sizes as $dimensions) {
            if (empty($dimensions['width']) ||
                empty($dimensions['height']) ||
                empty($dimensions['strategy']) ||
                !in_array($dimensions['strategy'], ['exact', 'portrait', 'landscape', 'auto', 'crop', 'square', 'fill'])
            ) {
                throw new \InvalidArgumentException('Dimensions array not valid, please fix.');
            }

            if (('fill' === $dimensions['strategy']) && empty($dimensions['background'])) {
                throw new \InvalidArgumentException('When using the "fill" strategy, you must supply a background color. HEX, rgb() or rgba() supported.');
            }
        }

        $results = [];
        foreach ($sizes as $dimensions) {
            $results[] = $this->send($image, $dimensions);
        }

        return $results;
    }


    /**
     * @param  string $image
     * @param  array  $dimensions
     * @return KrakenResponse
     */
    protected function send($image, array $dimensions = [])
    {
        switch ($this->service_type) {
            case 'url':
                $data = $this->getUrlData($image, $dimensions);
                break;
            case 'upload':
                $data = $this->getUploadData($image, $dimensions);
                break;
        }

        $this->logger->debug('Sending kraken request to '.$this->guzzle->getBaseUrl().' payload: '.print_r($data,1));

        $request  = $this->guzzle->post(null, null, ['body' => $data], ['debug' => true]);
        $response = $request->send();

        return new KrakenResponse($response);
    }


    /**
     * @param  string $image
     * @param  array  $dimensions
     * @param  bool   $as_json
     * @return array|string
     */
    protected function getUrlData($image, array $dimensions = [], $as_json = true)
    {
        $data = [
            'auth' => [
                'api_key'    => $this->api_key,
                'api_secret' => $this->api_secret
            ],
            'wait'         => $this->getWait(),
            'callback_url' => $this->getCallbackUrl(),
            'url'          => $image,
            'lossy'        => $this->use_lossy,
        ];

        if (count($dimensions)) {
            $data['resize'] = $dimensions;
        }

        return $as_json
            ? json_encode($data)
            : $data
        ;
    }


    /**
     * @param  string $image
     * @param  array  $dimensions
     * @return array
     */
    protected function getUploadData($image, array $dimensions = [])
    {
        $data = $this->getUrlData($image, $dimensions, false);
        unset($data['url']);

        return [
            'file' => '@'.$image,
            'data' => json_encode($data)
        ];
    }


    /**
     * @return string
     */
    private function getCallbackUrl()
    {
        return $this->callback_route
            ? $this->router->generate($this->callback_route)
            : ''
        ;
    }


    /**
     * @return bool
     */
    private function getWait()
    {
        return $this->callback_route
            ? false
            : true
        ;
    }
}
