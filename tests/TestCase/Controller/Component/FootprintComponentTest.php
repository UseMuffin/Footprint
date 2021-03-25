<?php
declare(strict_types=1);

namespace Muffin\Footprint\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

class FootprintComponentTest extends TestCase
{
    protected $fixtures = ['core.Users'];

    /**
     * @var \Cake\Controller\Controller;
     */
    protected $controller;

    /**
     * @var \Muffin\Footprint\Controller\Component\FootprintComponent
     */
    protected $footprint;

    public function setUp(): void
    {
        $this->controller = new Controller(null, null, null, new EventManager());

        $this->controller->loadComponent('Auth');
        $this->footprint = $this->controller->loadComponent('Muffin/Footprint.Footprint', [
            'userEntityClass' => Entity::class,
        ]);
        $this->controller->setRequest($this->controller->getRequest()
            ->withData('username', 'mariano')
            ->withData('password', 'cake'));
        $this->controller->Auth->setConfig('authenticate', ['Form']);

        $Users = $this->getTableLocator()->get('Users');
        $Users->updateAll(['password' => password_hash('cake', PASSWORD_BCRYPT)], []);
    }

    public function testImplementedEvents()
    {
        $result = $this->footprint->implementedEvents();
        $expected = [
            'Controller.initialize' => 'beforeFilter',
            'Auth.afterIdentify' => 'afterIdentify',
        ];
        $this->assertEquals($expected, $result);

        $listeners = $this->controller->getEventManager()->__debugInfo()['_listeners'];

        $this->assertSame(
            '3 listener(s)',
            $listeners['Controller.initialize']
        );

        $this->assertSame(
            '1 listener(s)',
            $listeners['Auth.afterIdentify']
        );
    }

    public function testAfterIdentify()
    {
        $this->controller->Auth->identify();

        $user = $this->footprint->getListener()->getUser();
        $this->assertInstanceOf(EntityInterface::class, $user);
        $this->assertTrue($user->isAccessible('id'));
        $this->assertTrue(isset($user->id));
    }

    /**
     * Tests for the case where the Auth component is not loaded, but FootprintComponent is.
     *
     * @return void
     */
    public function testNoAuthRegression()
    {
        $this->controller->components()->unload('Auth');
        unset($this->controller->Auth);
        EventManager::instance()->dispatch(new Event('Model.initialize', new Table(), ['id' => 1]));

        $this->assertNull($this->footprint->getListener()->getUser());
    }
}
