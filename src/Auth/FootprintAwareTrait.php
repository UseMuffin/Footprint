<?php
declare(strict_types=1);

namespace Muffin\Footprint\Auth;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
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
     * @var \Cake\Datasource\EntityInterface|null
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
    public function implementedEvents(): array
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
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function footprint(EventInterface $event): void
    {
        if (!$this->_listener) {
            $this->_listener = new FootprintListener($this->_getCurrentUser());
        }

        if ($event->getName() === 'Auth.afterIdentify') {
            $data = $event->getData();
            $this->_listener->setUser($this->_getCurrentUser($data[0]));

            return;
        }

        $event->getSubject()->getEventManager()->on($this->_listener);
    }

    /**
     * Returns an instance of the current authenticated user. If a `$user`
     * is provided, will overwrite the current logged in user instance.
     *
     * @param \Cake\Datasource\EntityInterface|array|null $user User.
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _getCurrentUser($user = null): ?EntityInterface
    {
        $this->_setCurrentUser($user);

        if (
            !$this->_currentUserInstance &&
            isset($this->_currentUserViewVar)
        ) {
            $this->_currentUserInstance = $this->viewBuilder()->getVar($this->_currentUserViewVar);
        }

        return $this->_currentUserInstance;
    }

    /**
     * Sets the current logged in user to `$user`. If none provided,
     * fallsback to `Cake\Controller\Component\AuthComponent::user()`.
     *
     * @param \Cake\Datasource\EntityInterface|array|null $user User.
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _setCurrentUser($user = null): ?EntityInterface
    {
        if ($user === null && !empty($this->Auth)) {
            $user = $this->Auth->user();
        }

        if ($user === null && !empty($this->Authentication)) {
            $identity = $this->Authentication->getIdentity();
            if ($identity) {
                $user = $identity->getData();
            }
        }

        if (!$user) {
            return null;
        }

        $this->_currentUserInstance = $this->_getUserInstance($user);

        return $this->_currentUserInstance;
    }

    /**
     * Creates instance of `$user`.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserInstance($user): EntityInterface
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
     * @return mixed
     */
    protected function _circumventEventManager(string $method, array $args = [])
    {
        EventManager::instance()->off('Model.initialize', [$this, 'footprint']);
        $result = call_user_func_array(
            [TableRegistry::getTableLocator()->get($this->_userModel), $method],
            $args
        );
        EventManager::instance()->on('Model.initialize', [$this, 'footprint']);

        return $result;
    }

    /**
     * Get user entity from data array.
     *
     * @param array|\Cake\Datasource\EntityInterface $user User data
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserInstanceFromArray($user): EntityInterface
    {
        $options = ['accessibleFields' => ['*' => true], 'validate' => false];

        return $this->_circumventEventManager('newEntity', [$user, $options]);
    }

    /**
     * Check given object is of user entity type.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User entity.
     * @return bool
     */
    protected function _checkUserInstanceOf($user): bool
    {
        $entityClass = $this->_circumventEventManager('getEntityClass');

        return $user instanceof $entityClass;
    }
}
