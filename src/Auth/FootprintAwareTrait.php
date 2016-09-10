<?php
namespace Muffin\Footprint\Auth;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Muffin\Footprint\Event\FootprintListener;

trait FootprintAwareTrait
{

    /**
     * User model.
     *
     * @var string
     */
    protected $_userModel = 'Users';

    /**
     * Instance of currently logged in user.
     *
     * @var \Cake\ORM\Entity
     */
    protected $_currentUserInstance;

    /**
     * Footprint listener instance.
     *
     * @var \Cake\Event\EventListenerInterface
     */
    protected $_listener;

    /**
     * Events this trait is interested in.
     *
     * @return array
     */
    public function implementedEvents()
    {
        EventManager::instance()->on('Model.initialize', [$this, 'footprint']);

        return parent::implementedEvents() + ['Auth.afterIdentify' => 'footprint'];
    }

    /**
     * Try and attach footprint listener to models.
     *
     * It also passing the user record to footprint listener after user is
     * identified by AuthComponent.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function footprint(Event $event)
    {
        if (!$this->_listener) {
            $this->_listener = new FootprintListener($this->_getCurrentUser());
        }

        if ($event->name() === 'Auth.afterIdentify') {
            $this->_listener->setUser($this->_getCurrentUser($event->data[0]));

            return;
        }

        $event->subject()->eventManager()->attach($this->_listener);
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

        if (!$this->_currentUserInstance &&
            !empty($this->viewVars[$this->_currentUserViewVar])
        ) {
            $this->_currentUserInstance = $this->viewVars[$this->_currentUserViewVar];
        }

        return $this->_currentUserInstance;
    }

    /**
     * Sets the current logged in user to `$user`. If none provided,
     * fallsback to `Cake\Controller\Component\AuthComponent::user()`.
     *
     * @param \Cake\ORM\Entity|array $user User.
     * @return \Cake\ORM\Entity|bool
     */
    protected function _setCurrentUser($user = null)
    {
        if ($user === null && !empty($this->Auth)) {
            $user = $this->Auth->user();
        }

        if (!$user) {
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
     * Temporarily disable the listener on `Model.initialize` when trying to
     * fetch instantiate the table and avoid an infinite loop.
     *
     * @param string $method Method name.
     * @param array $args Arguments to pass to the method.
     * @return \Cake\ORM\Table The users table.
     */
    protected function _circumventEventManager($method, $args = [])
    {
        EventManager::instance()->off('Model.initialize', [$this, 'footprint']);
        $result = call_user_func_array([TableRegistry::get($this->_userModel), $method], $args);
        EventManager::instance()->on('Model.initialize', [$this, 'footprint']);

        return $result;
    }

    /**
     * Get user entity from data array.
     *
     * @param array $user User data
     * @return \Cake\ORM\Entity
     */
    protected function _getUserInstanceFromArray($user)
    {
        $options = ['accessibleFields' => ['*' => true], 'validate' => false];

        return $this->_circumventEventManager('newEntity', [$user, $options]);
    }

    /**
     * Check given object is of user entity type.
     *
     * @param \Cake\ORM\Entity $user User entity.
     * @return bool
     */
    protected function _checkUserInstanceOf($user)
    {
        $entityClass = $this->_circumventEventManager('entityClass');

        return $user instanceof $entityClass;
    }
}
