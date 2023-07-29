<?php

namespace App\Middleware;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

class AuthMiddleware
{
    private $responseFactory;
    private $streamFactory;

    public function __construct()
    {
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        session_start();
        if (isset($_SESSION['PHPSESSID'])) {
            // User is logged in, proceed to the next middleware/route
            return $next($request, $response);
        } else {
            // User is not logged in, redirect to the sign-in endpoint
            // $stream = $this->streamFactory->createStream();
            // $stream->write('Unauthorized. Please sign in.');
            // $response = $this->responseFactory->createResponse(302);
            // return $response->withHeader('Location', '/signin')->withBody($stream);
            return $next($request, $response);
        }
    }
}
