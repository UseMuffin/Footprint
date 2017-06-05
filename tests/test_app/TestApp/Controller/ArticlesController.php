<?php
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\ORM\TableRegistry;
use Muffin\Footprint\Auth\FootprintAwareTrait;

/**
 * @property \Cake\ORM\Table Authors
 */
class ArticlesController extends Controller
{
    use FootprintAwareTrait;

    public function initialize()
    {
        parent::initialize();

        $this->Authors = TableRegistry::get('Muffin/Footprint.Authors');
        $this->Authors->addBehavior('Muffin/Footprint.Footprint');
    }
    
    /**
     * @return \Cake\ORM\Entity
     */
    public function getCurrentUserInstance()
    {
        return $this->_currentUserInstance;
    }
}
