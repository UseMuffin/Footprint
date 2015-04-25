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
            'Model.beforeSave' => -100,
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
     */
    public function __construct(Entity $user, array $config = [])
    {
        $this->config($config);
        $this->_currentUser = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function implementedEvents()
    {
        return array_map(function ($priority) {
            $callable = 'handleEvent';
            return compact('callable', 'priority');
        }, $this->config('events'));
    }

    /**
     * Universal callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Cake\ORM\Entity $entity Entity.
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
