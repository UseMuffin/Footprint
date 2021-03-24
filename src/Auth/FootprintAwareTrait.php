<?php
declare(strict_types=1);

namespace Muffin\Footprint\Auth;

use App\Model\Entity\User;
use Authentication\IdentityInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Muffin\Footprint\Event\FootprintListener;

/**
 * @property \Cake\Http\ServerRequest $request
 */
trait FootprintAwareTrait
{
    /**
     * User entity class name.
     *
     * @var string
     * @psalm-var class-string
     */
    protected $_footprintEntityClass = User::class;

    /**
     * Instance of currently logged in user.
     *
     * @var \Cake\Datasource\EntityInterface|null
     */
    protected $_currentUserInstance;

    /**
     * Footprint listener instance.
     *
     * @var \Muffin\Footprint\Event\FootprintListener
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
        if ($user === null) {
            if ($this->components()->has('Authentication')) {
                $identity = $this->Authentication->getIdentity();
                if ($identity) {
                    $user = $identity->getOriginalData();
                }
            } elseif ($this->components()->has('Auth')) {
                $user = $this->Auth->user();
            } else {
                $identity = $this->request->getAttribute('identity');
                if ($identity && $identity instanceof IdentityInterface) {
                    $user = $identity->getOriginalData();
                }
            }
        }

        if (!$user) {
            return null;
        }

        return $this->_currentUserInstance = $this->_getUserInstance($user);
    }

    /**
     * Creates instance of `$user`.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserInstance($user): EntityInterface
    {
        if ($user instanceof EntityInterface) {
            return $user;
        }

        return $this->_getUserInstanceFromArray($user);
    }

    /**
     * Get user entity from data array.
     *
     * @param array|\Cake\Datasource\EntityInterface $user User data
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserInstanceFromArray($user): EntityInterface
    {
        return new $this->_footprintEntityClass($user, [
            'guard' => false,
            'markClean' => true,
        ]);
    }
}
