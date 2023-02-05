<?php
declare(strict_types=1);

return [
    [
        'table' => 'articles',
        'columns' => [
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'length' => 255],
            'created_by' => ['type' => 'integer'],
            'modified_by' => ['type' => 'integer'],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
            ],
        ],
    ],
];
