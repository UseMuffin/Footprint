<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\ORM\Entity;
use Muffin\Footprint\Auth\FootprintAwareTrait;

class ArticlesController extends Controller
{
    use FootprintAwareTrait;

    public function initialize(): void
    {
        $this->_footprintEntityClass = Entity::class;
    }

    public function getCurrentUserInstance()
    {
        return $this->_currentUserInstance;
    }
}
