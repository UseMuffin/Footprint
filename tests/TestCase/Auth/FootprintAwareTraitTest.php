<?php
namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use TestApp\Controller\ArticlesController;

class FootprintAwareTraitTest extends TestCase
{
    public function setUp()
    {
        $this->controller = new ArticlesController(null, null, null, new EventManager());
        $this->controller->loadComponent('Auth');
    }

    public function testImplementedEvents()
    {
        $result = $this->controller->implementedEvents();
        $expected = EventManager::instance()->__debugInfo()['_listeners'];
        $this->assertSame(['Model.initialize' => '1 listener(s)'], $expected);
    }
}
