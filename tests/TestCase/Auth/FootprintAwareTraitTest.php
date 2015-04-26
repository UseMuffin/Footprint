<?php
namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use TestApp\Controller\ArticlesController;

class FootprintAwareTraitTest extends TestCase
{
    public function setUp()
    {
        $this->controller = new ArticlesController(null, null, null, new EventManager());
    }

    public function testImplementedEvents()
    {
        $result = $this->controller->implementedEvents();
        $expected = [
            'Controller.initialize' => 'beforeFilter',
            'Controller.beforeRender' => 'beforeRender',
            'Controller.beforeRedirect' => 'beforeRedirect',
            'Controller.shutdown' => 'afterFilter',
            'Model.initialize' => 'footprint'
        ];
        $this->assertEquals($expected, $result);
    }
}
