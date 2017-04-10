<?php
namespace Muffin\Footprint\Model\Behavior;

use ArrayObject;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
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
            $options = Hash::normalize((array)$options);
            foreach (array_keys($options) as $field) {
                if (!in_array($field, $config['propertiesMap']) && !isset($config['propertiesMap'][$field])) {
                    $config['propertiesMap'][] = $field;
                }
            }
            $this->_config['events'][$name] = $options;
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
        /* map all configured events to a single handler */
        return array_map(
            function () {
                return 'dispatch';
            },
            $this->_config['events']
        );
    }

    /**
     * Dispatch an event to the corresponding function
     * Called by the event system as instructed by implementedEvents()
     * @param Event $event Event.
     * @param Query|EntityInterface $data Query or Entity.
     * @param ArrayObject $options Options.
     * @return void
     */
    public function dispatch(Event $event, $data, ArrayObject $options)
    {
        $eventName = $event->name();
        if (empty($this->_config['events'][$eventName])) {
            return;
        }

        $fields = $this->_config['events'][$eventName];

        if ($data instanceof EntityInterface) {
            $this->_injectEntity($data, $options, $fields);
        } elseif ($data instanceof Query) {
            $this->_injectConditions($data, $options, $fields);
        } else {
            throw new \InvalidArgumentException("Event {$eventName} is not supported.");
        }
    }

    /**
     * Injects configured fields into finder conditions.
     *
     * @param \Cake\ORM\Query $query Query.
     * @param \ArrayObject $options Options.
     * @param array $fields Field configuration.
     * @return void
     */
    protected function _injectConditions(Query $query, ArrayObject $options, array $fields)
    {
        foreach (array_keys($fields) as $field) {
            $path = $this->config('propertiesMap.' . $field);

            $check = false;
            $query->traverseExpressions(function ($expression) use (&$check, $field, $query) {
                if ($expression instanceof IdentifierExpression) {
                    !$check && $check = $expression->getIdentifier() === $field;

                    return;
                }
                $alias = $this->_table->aliasField($field);
                !$check && $check = preg_match('/^' . $alias . '/', $expression->sql($query->valueBinder()));
            });

            if (!$check && $value = Hash::get((array)$options, $path)) {
                $query->where([$this->_table->aliasField($field) => $value]);
            }
        }
    }

    /**
     * Injects configured field values into entity if those fields are not dirty.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @param array $fields Field configuration.
     * @return void
     */
    protected function _injectEntity(EntityInterface $entity, ArrayObject $options, array $fields)
    {
        $new = $entity->isNew() !== false;

        foreach ($fields as $field => $when) {
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
