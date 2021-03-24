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
     * Footprint listener instance.
     *
     * @var \Muffin\Footprint\Event\FootprintListener
     */
    protected $_footprintListener;

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
     * It also passes the user record to footprint listener after user is
     * identified by AuthComponent.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function footprint(EventInterface $event): void
    {
        if (!$this->_footprintListener) {
            $this->_footprintListener = new FootprintListener($this->_getCurrentUser());
        }

        if ($event->getName() === 'Auth.afterIdentify') {
            $data = $event->getData();
            $this->_footprintListener->setUser($this->_getCurrentUser($data[0]));

            return;
        }

        $event->getSubject()->getEventManager()->on($this->_footprintListener);
    }

    /**
     * Returns an instance of the currently authenticated user. If a `$user`
     * is provided, will overwrite the current logged in user instance.
     *
     * @param \Cake\Datasource\EntityInterface|array|null $user User.
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _getCurrentUser($user = null): ?EntityInterface
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

        return $this->_getUserEntity($user);
    }

    /**
     * Creates instance of user entity.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserEntity($user): EntityInterface
    {
        if ($user instanceof EntityInterface) {
            return $user;
        }

        return $this->_getUserEntityFromArray($user);
    }

    /**
     * Get user entity from data array.
     *
     * @param array|\Cake\Datasource\EntityInterface $user User data
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _getUserEntityFromArray($user): EntityInterface
    {
        return new $this->_footprintEntityClass($user, [
            'guard' => false,
            'markClean' => true,
        ]);
    }
}
