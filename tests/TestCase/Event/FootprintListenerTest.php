<?php
namespace Muffin\Footprint\Test\TestCase\Event\Listener;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Muffin\Footprint\Event\FootprintListener;

class FootprintListenerTest extends TestCase
{
    public function setUp(): void
    {
        $this->listener = new FootprintListener();
    }

    public function testImplementedEvents()
    {
        $result = $this->listener->implementedEvents();
        $expected = [
            'Model.beforeRules' => ['priority' => -100, 'callable' => 'handleEvent'],
            'Model.beforeSave' => ['priority' => -100, 'callable' => 'handleEvent'],
            'Model.beforeFind' => ['priority' => -100, 'callable' => 'handleEvent'],
            'Model.beforeDelete' => ['priority' => -100, 'callable' => 'handleEvent'],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testHandleEvent()
    {
        $entity = new Entity(['id' => 1]);
        $this->listener->setUser($entity);

        $options = new \ArrayObject();
        $this->listener->handleEvent(
            new Event('Model.save'),
            new Entity(['title' => 'article']),
            $options
        );

        $this->assertSame($entity, $options['_footprint']);
    }
}
