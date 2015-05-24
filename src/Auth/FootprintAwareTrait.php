<?php
namespace Muffin\Footprint\Auth;

use Cake\Datasource\RepositoryInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Muffin\Footprint\Event\FootprintListener;
use RuntimeException;

trait FootprintAwareTrait
{
    /**
     * Stack of loaded models.
     *
     * @var array
     */
    protected $_loadedModels = [];

    /**
     * Instance of currently logged in user.
     *
     * @var \Cake\ORM\Entity
     */
    protected $_currentUserInstance;

    /**
     * Events this trait is interested in.
     *
     * @return array
     */
    public function implementedEvents()
    {
        EventManager::instance()->on('Model.initialize', [$this, 'footprint']);

        return parent::implementedEvents();
    }

    /**
     * Try and attach footprint listener to models.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function footprint(Event $event)
    {
        try {
            $listener = new FootprintListener($this->_getCurrentUser());
            $this->_attachRecursive($listener, $event->subject());
        } catch (RuntimeException $e) {
        }
    }

    /**
     * Recursively attaches the `Muffin\Footprint\Event\FootprintListener` to
     * the loaded model and all it's associations.
     *
     * @param \Muffin\Footprint\Event\FootprintListener $listener Listener.
     * @param \Cake\Datasource\RepositoryInterface $modelClass Repository.
     * @return \Cake\Datasource\RepositoryInterface
     */
    protected function _attachRecursive(FootprintListener $listener, RepositoryInterface $modelClass)
    {
        $alias = $modelClass->alias();

        if (!in_array($alias, $this->_loadedModels)) {
            $this->_loadedModels[] = $alias;
            $modelClass->eventManager()->attach($listener);

            foreach ($modelClass->associations()->keys() as $association) {
                $assocModelClass = $modelClass->association($association)->target();
                $this->_attachRecursive($listener, $assocModelClass);
            }
        }

        return $modelClass;
    }

    /**
     * Returns an instance of the current authenticated user. If a `$user`
     * is provided, will overwrite the current logged in user instance.
     *
     * @param \Cake\ORM\Entity|array $user User.
     * @return \Cake\ORM\Entity
     */
    protected function _getCurrentUser($user = null)
    {
        $this->_setCurrentUser($user);

        if (!$this->_currentUserInstance) {
            if (!empty($this->viewVars[$this->_currentUserViewVar])) {
                $this->_currentUserInstance = $this->viewVars[$this->_currentUserViewVar];
            }
            if (!$this->_currentUserInstance) {
                throw new RuntimeException();
            }
        }

        return $this->_currentUserInstance;
    }

    /**
     * Sets the current logged in user to `$user`. If none provided,
     * fallsback to `Cake\Controller\Component\AuthComponent::user()`.
     *
     * @param \Cake\ORM\Entity|array $user User.
     * @return \Cake\ORM\Entity
     */
    protected function _setCurrentUser($user = null)
    {
        if (!$user && !$user = $this->Auth->user()) {
            return false;
        }

        $this->_currentUserInstance = $this->_getUserInstance($user);
        return $this->_currentUserInstance;
    }

    /**
     * Creates instance of `$user`.
     *
     * @param \Cake\ORM\Entity|array $user User.
     * @return \Cake\ORM\Entity
     */
    protected function _getUserInstance($user)
    {
        if ($this->_checkUserInstanceOf($user)) {
            return $user;
        }

        return $this->_getUserInstanceFromArray($user);
    }

    /**
     * Get user entity from data array.
     *
     * @param array $user User data
     * @return \Cake\ORM\Entity
     */
    protected function _getUserInstanceFromArray($user)
    {
        if (!$userModel = $this->_userModel) {
            $userModel = 'Users';
        }

        return TableRegistry::get($userModel)
            ->newEntity($user);
    }

    /**
     * Check given object is of user entity type.
     *
     * @param \Cake\ORM\Entity $user User entity.
     * @return bool
     */
    protected function _checkUserInstanceOf($user)
    {
        if (!$userModel = $this->_userModel) {
            $userModel = 'Users';
        }

        $entityClass = TableRegistry::get($userModel)->entityClass();
        return $user instanceof $entityClass;
    }
}
