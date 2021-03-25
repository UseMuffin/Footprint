<?php
declare(strict_types=1);

namespace Muffin\Footprint\Controller\Component;

use Authentication\IdentityInterface;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Muffin\Footprint\Event\FootprintListener;

class FootprintComponent extends Component
{
    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'userEntityClass' => 'App\Model\Entity\User',
    ];

    /**
     * Footprint listener instance.
     *
     * @var \Muffin\Footprint\Event\FootprintListener|null
     */
    protected $_listener;

    /**
     * Events this component is interested in.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.initialize' => 'beforeFilter',
            'Auth.afterIdentify' => 'afterIdentify',
        ];
    }

    /**
     * Attach footprint listener to models.
     *
     * @return void
     */
    public function beforeFilter(): void
    {
        EventManager::instance()->on('Model.initialize', function (EventInterface $event) {
            $event->getSubject()->getEventManager()->on($this->getListener());
        });
    }

    /**
     * Callback for `Auth.afterIdentify` event of AuthComponent.
     *
     * Sets user record to the footprint listener.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function afterIdentify(EventInterface $event): void
    {
        /**
         * @psalm-suppress PossiblyNullArrayAccess
         * @psalm-suppress PossiblyNullArgument
         */
        $this->getListener()->setUser($this->_getCurrentUser($event->getData()[0]));
    }

    /**
     * Get footprint listener
     *
     * @return \Muffin\Footprint\Event\FootprintListener
     */
    public function getListener(): FootprintListener
    {
        if (!$this->_listener) {
            $this->_listener = new FootprintListener($this->_getCurrentUser());
        }

        return $this->_listener;
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
            if ($this->getController()->components()->has('Authentication')) {
                /** @psalm-suppress UndefinedMagicPropertyFetch */
                $identity = $this->getController()->Authentication->getIdentity();
                if ($identity) {
                    $user = $identity->getOriginalData();
                }
            } elseif ($this->getController()->components()->has('Auth')) {
                /** @psalm-suppress UndefinedMagicPropertyFetch */
                $user = $this->getController()->Auth->user();
            } else {
                $identity = $this->getController()->getRequest()->getAttribute('identity');
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
        /** @psalm-var class-string<\Cake\Datasource\EntityInterface> $userEntityClass */
        $userEntityClass = $this->getConfig('userEntityClass');

        return new $userEntityClass($user, [
            'guard' => false,
            'markClean' => true,
        ]);
    }
}
