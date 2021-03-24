<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Muffin\Footprint\Auth\FootprintAwareTrait;

class ArticlesController extends Controller
{
    use FootprintAwareTrait {
        _getCurrentUser as _getCurrentUserFromTrait;
    }

    public function initialize(): void
    {
        $this->_footprintEntityClass = Entity::class;
    }

    protected function _getCurrentUser($user = null): ?EntityInterface
    {
        return $this->_currentUserInstance = $this->_getCurrentUserFromTrait($user);
    }

    public function getCurrentUserInstance()
    {
        return $this->_currentUserInstance;
    }
}
