<?php

namespace Pompdelux\KrakenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function callbackAction(Request $request)
    {
        error_log(print_r($request->getContent(), 1));
        return new Response();
    }

    public function testAction()
    {
        $kraken = $this->container->get('pompdelux.kraken.test');
        $response = $kraken->squeeze('http://static.pompdelux.com/images/frontpage/Box2_ALL_01.jpg');

        error_log(print_r($response->getResponse(), 1));
        return new Response('xxx');
    }
}
