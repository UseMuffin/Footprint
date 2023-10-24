<?php
namespace Muffin\Footprint\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    public string $table = 'articles';

    public array $records = [
        [
            'title' => 'article 1',
            'created_by' => 1,
            'modified_by' => 1,
            'manager_id' => 10,
        ],
        [
            'title' => 'article 2',
            'created_by' => 1,
            'modified_by' => 2,
            'manager_id' => null,
        ],
        [
            'title' => 'article 3',
            'created_by' => 2,
            'modified_by' => 1,
            'company_id' => 2,
            'manager_id' => null,
        ],
        [
            'title' => 'find article',
            'created_by' => 4,
            'modified_by' => 4,
            'company_id' => 2,
            'manager_id' => null,
        ],
        [
            'title' => 'final article',
            'created_by' => 3,
            'modified_by' => 4,
            'company_id' => 4,
            'manager_id' => null,
        ],
        [
            'title' => 'penultimate article',
            'created_by' => 4,
            'modified_by' => 4,
            'company_id' => 1,
            'manager_id' => null,
        ]
    ];
}
