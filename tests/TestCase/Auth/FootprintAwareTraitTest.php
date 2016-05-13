<?php
namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use TestApp\Controller\ArticlesController;

class FootprintAwareTraitTest extends TestCase
{

    public $fixtures = ['core.Users', 'plugin.Muffin/Footprint.Articles'];

    public function setUp()
    {
        $this->controller = new ArticlesController(null, null, null, new EventManager());

        $this->controller->loadComponent('Auth');
        $this->controller->Auth->request->data = [
            'username' => 'mariano',
            'password' => 'cake'
        ];
        $this->controller->Auth->config('authenticate', ['Form']);

        $Users = TableRegistry::get('Users');
        $Users->updateAll(['password' => password_hash('cake', PASSWORD_BCRYPT)], []);
    }

    public function testImplementedEvents()
    {
        $result = $this->controller->implementedEvents();
        $expected = [
            'Controller.initialize' => 'beforeFilter',
            'Controller.beforeRender' => 'beforeRender',
            'Controller.beforeRedirect' => 'beforeRedirect',
            'Controller.shutdown' => 'afterFilter',
            'Auth.afterIdentify' => 'footprint'
        ];
        $this->assertEquals($expected, $result);

        $expected = EventManager::instance()->__debugInfo()['_listeners'];
        $this->assertSame(['Model.initialize' => '1 listener(s)'], $expected);
    }

    public function testAfterIdentify()
    {
        $this->assertNull($this->controller->getCurrentUserInstance());

        $this->controller->Auth->identify();

        $user = $this->controller->getCurrentUserInstance();
        $this->assertInstanceOf('\Cake\ORM\Entity', $user);
        $this->assertTrue($user->accessible('id'));
        $this->assertTrue(isset($user->id));
    }

    /**
     * Tests for the case where the Auth component is not loaded, but FootprintAwareTrait is.
     *
     * @return void
     */
    public function testNoAuthRegression()
    {
        unset($this->controller->Auth);
        $this->controller->footprint(new Event('Model.initialize', new Table(), ['id' => 1]));

        $this->assertNull($this->controller->getCurrentUserInstance());
    }
}
