<?php
namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class FootprintBehaviorTest2 extends TestCase
{
    public $fixtures = [
        'plugin.Muffin/Footprint.Articles',
    ];

    public function setUp()
    {
        parent::setUp();

        $table = TableRegistry::get('Muffin/Footprint.Articles');
        $table->addBehavior('Muffin/Footprint.Footprint', [
            'events' => [
                'Model.beforeRules' => [
                    'created_by' => 'new',
                    'modified_by' => 'always',
                ],
            ],
        ]);

        $this->Table = $table;
        $this->footprint = new Entity([
            'id' => 2,
            'company' => new Entity(['id' => 5])
        ]);
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    public function testSave()
    {
        /* test if beforeRules event gives the same result on save as beforeSave would */

        $entity = new Entity(['title' => 'new article']);
        $entity = $this->Table->save($entity, ['_footprint' => $this->footprint]);
        $expected = ['id' => $entity->id, 'title' => 'new article', 'created_by' => 2, 'modified_by' => 2];
        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by']));

        $footprint = new Entity([
            'id' => 3
        ]);
        $entity->title = 'new title';
        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
        $expected = ['id' => $entity->id, 'title' => 'new title', 'created_by' => 2, 'modified_by' => 3];
        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by']));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Event Model.beforeMarshal is not supported.
     */
    public function testDispatchException()
    {
        $behavior = $this->Table->behaviors()->Footprint;
        $behavior->config('events', ['Model.beforeMarshal' => ['modified_by']]);
        $this->Table->eventManager()->on('Model.beforeMarshal', [$behavior, 'dispatch']);
        $entity = $this->Table->newEntity([]);
    }
}
