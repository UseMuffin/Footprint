<?php
declare(strict_types=1);

namespace Muffin\Footprint\Test\TestCase;

use Cake\Core\PluginApplicationInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Muffin\Footprint\FootprintPlugin;
use Muffin\Footprint\Middleware\FootprintMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

class FootprintPluginTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Muffin/Footprint.Articles',
    ];

    public function tearDown(): void
    {
        (new ReflectionProperty(FootprintPlugin::class, 'listener'))->setValue(null, null);

        parent::tearDown();
    }

    public function testMiddlewarePassesIdentityToDefaultBehavior(): void
    {
        $eventManager = new EventManager();
        $app = $this->createStub(PluginApplicationInterface::class);
        $app->method('getEventManager')->willReturn($eventManager);
        (new FootprintPlugin())->bootstrap($app);

        $table = new Table([
            'table' => 'articles',
            'alias' => 'Articles',
            'connection' => ConnectionManager::get('test'),
            'eventManager' => $eventManager,
        ]);
        $table->addBehavior('Muffin/Footprint.Footprint');

        $user = new Entity(['id' => 42]);
        $request = (new ServerRequest())->withAttribute('identity', $user);
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)
            ->willReturnCallback(function () use ($table, $response): Response {
                $entity = $table->newEntity(['title' => 'With request identity']);
                $saved = $table->saveOrFail($entity);

                $this->assertSame(42, $saved->created_by);
                $this->assertSame(42, $saved->modified_by);

                return $response;
            });

        $this->assertSame($response, (new FootprintMiddleware())->process($request, $handler));
    }

    public function testInitializeWithoutSubject(): void
    {
        $eventManager = new EventManager();
        $app = $this->createStub(PluginApplicationInterface::class);
        $app->method('getEventManager')->willReturn($eventManager);
        (new FootprintPlugin())->bootstrap($app);

        $event = new Event('Model.initialize');
        $this->assertSame($event, $eventManager->dispatch($event));
    }
}
