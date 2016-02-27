<?php
namespace Muffin\Footprint\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
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
            $this->config('events.' . $name, Hash::normalize((array)$options));
            foreach (array_keys($this->config('events.' . $name)) as $field) {
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
     * Injects configured fields into finder conditions.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Cake\ORM\Query $query Query.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeFind(Event $event, Query $query, ArrayObject $options)
    {
        $eventName = $event->name();
        $config = $this->config('events.' . $eventName);

        foreach (array_keys($config) as $field) {
            $path = $this->config('propertiesMap.' . $field);
            if ($value = Hash::get((array)$options, $path)) {
                $query->where([$field => $value]);
            }
        }
    }

    /**
     * Injects configured field values into entity if those fields are not dirty.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Cake\ORM\Entity $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
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
    }
}
