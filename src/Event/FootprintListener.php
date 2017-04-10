<?php
namespace Muffin\Footprint\Event;

use ArrayObject;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;

class FootprintListener implements EventListenerInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
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
     * @var \Cake\ORM\Entity
     */
    protected $_currentUser;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Entity $user User entity.
     * @param array $config Configuration list.
     */
    public function __construct(Entity $user = null, array $config = [])
    {
        $this->config($config);
        $this->_currentUser = $user;
    }

    /**
     * {@inheritDoc}
     */
    public function implementedEvents()
    {
        return array_map(function ($priority) {
            $callable = 'handleEvent';

            return compact('callable', 'priority');
        }, $this->config('events'));
    }

    /**
     * Set current user entity.
     *
     * @param \Cake\ORM\Entity $entity Entity.
     * @return void
     */
    public function setUser(Entity $entity)
    {
        $this->_currentUser = $entity;
    }

    /**
     * Universal callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @param mixed $ormObject Query or Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function handleEvent(Event $event, $ormObject, $options)
    {
        $key = $this->config('optionKey');
        if (empty($options[$key]) && !empty($this->_currentUser)) {
            $options[$key] = $this->_currentUser;
        }
    }
}
