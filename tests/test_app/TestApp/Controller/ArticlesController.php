<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Muffin\Footprint\Auth\FootprintAwareTrait;

class ArticlesController extends Controller
{
    use FootprintAwareTrait;

    public function getCurrentUserInstance()
    {
        return $this->_currentUserInstance;
    }
}
