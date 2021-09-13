<?php
declare(strict_types=1);

namespace Muffin\Footprint\Middleware;

use Cake\Datasource\EntityInterface;
use Muffin\Footprint\Plugin;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FootprintMiddleware implements MiddlewareInterface
{
    /**
     * Config
     *
     * @var array
     */
    protected $config = [
        'identityAttribute' => 'identity',
    ];

    /**
     * Constructor
     *
     * @param array $config Array of configuration settings.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + $this->config;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $footprint = $request->getAttribute($this->config['identityAttribute']);
        if ($footprint) {
            if (!$footprint instanceof EntityInterface) {
                $footprint = $footprint->getOriginalData();
            }

            Plugin::getListener()->setUser($footprint);
        }

        return $handler->handle($request);
    }
}
