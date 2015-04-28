<?php
namespace Muffin\Footprint\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    public $table = 'articles';

    public $fields = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'length' => 255],
        'created_by' => ['type' => 'integer'],
        'modified_by' => ['type' => 'integer'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public $records = [
        [
            'title' => 'article 1',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        [
            'title' => 'article 2',
            'created_by' => 1,
            'modified_by' => 2,
        ],
        [
            'title' => 'article 3',
            'created_by' => 2,
            'modified_by' => 1,
        ],
    ];
}
