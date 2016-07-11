<?php
namespace Muffin\Footprint\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AuthorsFixture extends TestFixture
{
    public $table = 'authors';

    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'length' => 255],
        'created_by' => ['type' => 'integer'],
        'modified_by' => ['type' => 'integer'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public $records = [
        [
            'name' => 'author 1',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        [
            'name' => 'author 2',
            'created_by' => 1,
            'modified_by' => 2,
        ],
        [
            'name' => 'author 3',
            'created_by' => 2,
            'modified_by' => 1,
        ],
    ];
}
