<?php
declare(strict_types=1);

namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

class FootprintBehaviorTest extends TestCase
{
    protected $fixtures = [
        'plugin.Muffin/Footprint.Articles',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $table = $this->getTableLocator()->get('Muffin/Footprint.Articles');
        $table->addBehavior('Muffin/Footprint.Footprint', [
            'events' => [
                'Model.beforeSave' => [
                    'created_by' => 'new',
                    'modified_by' => 'always',
                    'company_id' => 'always',
                ],
                'Model.beforeFind' => 'created_by',
            ],
            'propertiesMap' => [
                'company_id' => '_footprint.company.id',
            ],
        ]);

        $this->Table = $table;
        $this->footprint = new Entity([
            'id' => 2,
            'company' => new Entity(['id' => 5]),
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    public function testSave()
    {
        $entity = new Entity(['title' => 'new article']);
        $entity = $this->Table->save($entity, ['_footprint' => $this->footprint]);
        $expected = [
            'id' => $entity->id,
            'title' => 'new article',
            'created_by' => 2,
            'modified_by' => 2,
            'company_id' => 5,
        ];
        $this->assertSame(
            $expected,
            $entity->extract(['id', 'title', 'created_by', 'modified_by', 'company_id'])
        );

        $footprint = new Entity([
            'id' => 3,
        ]);
        $entity->title = 'new title';
        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
        $expected = ['id' => $entity->id, 'title' => 'new title', 'created_by' => 2, 'modified_by' => 3];
        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by']));

        $entity = new Entity(['title' => 'without footprint']);
        $entity = $this->Table->save($entity);
        $expected = ['id' => $entity->id, 'title' => 'without footprint', 'created_by' => null, 'modified_by' => null];
        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by']));
    }

    public function testFind()
    {
        $result = $this->Table->find('all', ['_footprint' => $this->footprint])
            ->enableHydration(false)
            ->first();

        $expected = ['id' => 3, 'title' => 'article 3', 'created_by' => 2, 'modified_by' => 1];
        $this->assertSame($expected, $result);

        // Test to show value of "id" is not used from footprint if
        // "Articles.created_by" is already set in condition.
        $result = $this->Table->find('all', ['_footprint' => $this->footprint])
            ->where(['Articles.created_by' => 1])
            ->enableHydration(false)
            ->first();

        $expected = ['id' => 1, 'title' => 'article 1', 'created_by' => 1, 'modified_by' => 1];
        $this->assertSame($expected, $result);
    }

    public function testInjectEntityException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('When should be one of "always", "new" or "existing". The passed value "invalid" is invalid');

        $this->Table->behaviors()->Footprint->setConfig(
            'events',
            [
                'Model.beforeSave' => [
                    'created_by' => 'invalid',
                ],
            ]
        );
        $entity = new Entity(['title' => 'new article']);
        $entity = $this->Table->save($entity, ['_footprint' => $this->footprint]);
    }

    public function testDispatchException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Event "Model.beforeMarshal" is not supported.');

        $behavior = $this->Table->behaviors()->Footprint;
        $behavior->setConfig('events', ['Model.beforeMarshal' => ['modified_by']]);
        $this->Table->getEventManager()->on('Model.beforeMarshal', [$behavior, 'dispatch']);
        $this->Table->newEntity([]);
    }
}
