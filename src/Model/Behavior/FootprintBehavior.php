<?php
namespace Muffin\Footprint\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use UnexpectedValueException;

class FootprintBehavior extends Behavior
{

    /**
     * Default config.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'events' => [
            'Model.beforeSave' => [
                'created_by' => 'new',
                'modified_by' => 'always',
            ]
        ],
        'optionKey' => '_footprint',
        'primaryKey' => 'id',
        'propertiesMap' => []
    ];

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        if (isset($config['events'])) {
            $this->config('events', $config['events'], false);
        }

        $config = $this->config();

        foreach ($config['events'] as $name => $options) {
            foreach ($options as $field => $when) {
                if (!in_array($field, $config['propertiesMap']) && !isset($config['propertiesMap'][$field])) {
                    $config['propertiesMap'][] = $field;
                }
            }
        }

        foreach ($config['propertiesMap'] as $property => $map) {
            if (is_numeric($property)) {
                unset($config['propertiesMap'][$property]);
                $property = $map;
                $map = $config['primaryKey'];
                $config['propertiesMap'] += [$property => $map];
            }

            if (strpos($map, '.') === false) {
                $config['propertiesMap'][$property] = implode('.', [$config['optionKey'], $map]);
            }
        }

        $this->config('propertiesMap', $config['propertiesMap'], false);
    }

    /**
     * {@inheritDoc}
     */
    public function implementedEvents()
    {
        return array_fill_keys(array_keys($this->config('events')), 'handleEvent');
    }

    /**
     * Event handler.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Cake\ORM\Entity $entity Event.
     * @param \ArrayObject $options Options.
     * @return bool
     */
    public function handleEvent(Event $event, Entity $entity, ArrayObject $options)
    {
        $eventName = $event->name();
        $events = $this->config('events');

        $new = $entity->isNew() !== false;

        foreach ($events[$eventName] as $field => $when) {
            if (!in_array($when, ['always', 'new', 'existing'])) {
                throw new UnexpectedValueException(
                    sprintf('When should be one of "always", "new" or "existing". The passed value "%s" is invalid', $when)
                );
            }

            if ($entity->dirty($field)) {
                continue;
            }

            if ($when === 'always' ||
                ($when === 'new' && $new) ||
                ($when === 'existing' && !$new)
            ) {
                $entity->set(
                    $field,
                    current(Hash::extract((array)$options, $this->config('propertiesMap.' . $field)))
                );
            }
        }

        return true;
    }
}
