<?php
/*
 * This file is part of the hanzo package.
 *
 * (c) Ulrik Nielsen <un@bellcom.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pompdelux\Bundle\KrakenBundle;

use Guzzle\Http\Message\Response;

class KrakenResponse
{
    /**
     * @var \Guzzle\Http\Message\Response
     */
    private $response;

    /**
     * Setup, accepts the Guzzle Response object as parameter.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get response status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->response->isSuccessful();
    }

    /**
     * Get the response of the query.
     * Will be the json decoded data if success, else the error message.
     *
     * @return array|string
     */
    public function getResponse()
    {
        if (false === $this->response->isSuccessful()) {
            return $this->response->getMessage();
        }

        return $this->response->json();
    }
}
