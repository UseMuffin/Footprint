<?php
declare(strict_types=1);

namespace Muffin\Footprint\Test\TestCase\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

class FootprintBehaviorTest extends TestCase
{
    protected Table $Table;

    protected Entity $footprint;

    protected array $fixtures = [
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
                    'manager_id' => function ($entity): bool {
                        return $entity->company_id == 1;
                    }
                ],
                'Model.beforeFind' => [
                    'created_by',
                    'company_id',
                ]
            ],
            'propertiesMap' => [
                'company_id' => '_footprint.company.id',
                'manager_id' => '_footprint.manager.id',
            ],
        ]);

        $this->Table = $table;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    public function testSave()
    {
        // Properties may still be assigned even if
        // closure would be satisfied.
        $entity = new Entity(['title' => 'new article', 'manager_id' => 7]);
        $footprint = new Entity([
            'id' => 2,
            'company' => new Entity(['id' => 1]),
            'manager' => new Entity(['id' => 10]),
        ]);

        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
        $expected = [
            'id' => $entity->id,
            'title' => 'new article',
            'created_by' => 2,
            'modified_by' => 2,
            'company_id' => 1,
            'manager_id' => 7,
        ];

        $this->assertSame(
            $expected,
            $entity->extract(['id', 'title', 'created_by', 'modified_by', 'company_id', 'manager_id'])
        );

        // Closure fields won't set if disallowed
        // even if provided.
        $entity = new Entity();
        $entity->title = 'new title';
        $footprint = new Entity([
            'id' => 3,
            'company' => new Entity(['id' => 5]),
            'manager' => new Entity(['id' => 4]),
        ]);

        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
        $expected = [
            'id' => $entity->id,
            'title' => 'new title',
            'created_by' => 3,
            'modified_by' => 3,
            'company_id' => 5,
            'manager_id' => null,
        ];

        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by', 'company_id', 'manager_id']));

        // Fields won't set if a footprint isn't provided
        $entity = new Entity(['title' => 'without footprint']);

        $entity = $this->Table->save($entity);
        $expected = [
            'id' => $entity->id,
            'title' => 'without footprint',
            'created_by' => null,
            'modified_by' => null,
            'manager_id' => null,
        ];

        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by', 'manager_id']));

        // Satisfying closure manually still permits
        // explicit field assignments
        $entity = new Entity(['title' => 'different manager', 'company_id' => 1]);
        $footprint = new Entity([
            'id' => 3,
            'company' => new Entity(['id' => 5]),
            'manager' => new Entity(['id' => 4]),
        ]);

        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
        $expected = [
            'id' => $entity->id,
            'title' => 'different manager',
            'created_by' => 3,
            'modified_by' => 3,
            'company_id' => 1,
            'manager_id' => 4,
        ];

        $this->assertSame($expected, $entity->extract(['id', 'title', 'created_by', 'modified_by', 'company_id', 'manager_id']));
    }

    public function testFind()
    {
        $footprint = new Entity(['id' => 4]);

        $result = $this->Table->find('all', _footprint: $footprint)
            ->enableHydration(false)
            ->first();

        $expected = [
            'id' => 4,
            'title' => 'find article',
            'created_by' => 4,
            'modified_by' => 4,
            'company_id' => 2,
            'manager_id' => null,
        ];
        $this->assertSame($expected, $result);

        // Test to show value of "id" is not used from footprint if
        // "Articles.created_by" is already set in condition.
        $result = $this->Table->find('all', _footprint: $footprint)
            ->where(['Articles.created_by' => 3])
            ->enableHydration(false)
            ->first();

        $expected = [
            'id' => 5,
            'title' => 'final article',
            'created_by' => 3,
            'modified_by' => 4,
            'company_id' => 4,
            'manager_id' => null,
        ];
        $this->assertSame($expected, $result);

        // Test to show value of "id" is not used from footprint even
        // "Articles.manager_id" validates the Model.beforeSave closure
        $result = $this->Table->find('all', _footprint: $footprint)
        ->where(['Articles.company_id' => 1])
        ->enableHydration(false)
        ->first();

        $expected = [
            'id' => 6,
            'title' => 'penultimate article',
            'created_by' => 4,
            'modified_by' => 4,
            'company_id' => 1,
            'manager_id' => null,
        ];
        $this->assertSame($expected, $result);
    }

    public function testInjectEntityException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('When should be one of "always", "new" or "existing", or a closure that takes an EntityInterface and returns a bool. The passed value "invalid" is invalid');

        $footprint = new Entity([
            'id' => 2,
        ]);

        $this->Table->behaviors()->Footprint->setConfig(
            'events',
            [
                'Model.beforeSave' => [
                    'created_by' => 'invalid',
                ],
            ]
        );
        $entity = new Entity(['title' => 'new article']);
        $entity = $this->Table->save($entity, ['_footprint' => $footprint]);
    }
}
