<?php
declare(strict_types=1);

namespace Muffin\Footprint\Event;

use ArrayObject;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Query\SelectQuery;

class FootprintListener implements EventListenerInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'events' => [
            'Model.beforeFind' => -100,
            'Model.beforeRules' => -100,
            'Model.beforeSave' => -100,
            'Model.beforeDelete' => -100,
        ],
        'optionKey' => '_footprint',
    ];

    /**
     * Instance of currently logged in user.
     *
     * @var \Cake\Datasource\EntityInterface|null
     */
    protected ?EntityInterface $_currentUser;

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\EntityInterface|null $user User entity.
     * @param array<string, mixed> $config Configuration list.
     */
    public function __construct(?EntityInterface $user = null, array $config = [])
    {
        $this->setConfig($config);
        $this->_currentUser = $user;
    }

    /**
     * Events this listener is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return array_map(function ($priority) {
            $callable = 'handleEvent';

            return compact('callable', 'priority');
        }, $this->getConfig('events'));
    }

    /**
     * Set current user entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    public function setUser(EntityInterface $entity): void
    {
        $this->_currentUser = $entity;
    }

    /**
     * Get current user entity.
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function getUser(): ?EntityInterface
    {
        return $this->_currentUser;
    }

    /**
     * Universal callback.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface|\Cake\ORM\Query\SelectQuery $ormObject Query or Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function handleEvent(EventInterface $event, EntityInterface|SelectQuery $ormObject, ArrayObject $options): void
    {
        $key = $this->getConfig('optionKey');
        if ($this->_currentUser && empty($options[$key])) {
            $options[$key] = $this->_currentUser;
        }
    }
}
