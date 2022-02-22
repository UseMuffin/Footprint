<?php
declare(strict_types=1);

namespace Muffin\Footprint\Model\Behavior;

use ArrayObject;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use InvalidArgumentException;
use UnexpectedValueException;

class FootprintBehavior extends Behavior
{
    /**
     * Default config.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'events' => [
            'Model.beforeSave' => [
                'created_by' => 'new',
                'modified_by' => 'always',
            ],
        ],
        'optionKey' => '_footprint',
        'primaryKey' => 'id',
        'propertiesMap' => [],
    ];

    /**
     * Intialize the behavior.
     *
     * @param array<string, mixeD> $config Config options.
     * @return void
     */
    public function initialize(array $config): void
    {
        if (isset($config['events'])) {
            $this->setConfig('events', $config['events'], false);
        }

        $config = $this->getConfig();

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

        $this->setConfig('propertiesMap', $config['propertiesMap'], false);
    }

    /**
     * Events this behavior is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        /** @phpstan-ignore-next-line */
        return array_fill_keys(
            array_keys($this->_config['events']),
            'dispatch'
        );
    }

    /**
     * Dispatch an event to the corresponding function.
     *
     * Called by the event manager as per list provided by implementedEvents().
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\ORM\Query|\Cake\Datasource\EntityInterface $data Query or Entity.
     * @param \ArrayObject $options Options.
     * @return void
     * @throws \InvalidArgumentException If method is called with an unsupported event.
     */
    public function dispatch(EventInterface $event, $data, ArrayObject $options): void
    {
        $eventName = $event->getName();
        if (empty($this->_config['events'][$eventName])) {
            return;
        }

        $fields = $this->_config['events'][$eventName];

        if ($data instanceof EntityInterface) {
            $this->_injectEntity($data, $options, $fields);

            return;
        }

        if ($data instanceof Query) {
            $this->_injectConditions($data, $options, $fields);

            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Event "%s" is not supported.',
            $eventName
        ));
    }

    /**
     * Injects configured fields into finder conditions.
     *
     * @param \Cake\ORM\Query $query Query.
     * @param \ArrayObject $options Options.
     * @param array $fields Field configuration.
     * @return void
     */
    protected function _injectConditions(Query $query, ArrayObject $options, array $fields): void
    {
        foreach (array_keys($fields) as $field) {
            $path = $this->getConfig('propertiesMap.' . $field);

            $check = false;
            $query->traverseExpressions(function (ExpressionInterface $expression) use (&$check, $field, $query) {
                if ($expression instanceof IdentifierExpression) {
                    !$check && $check = $expression->getIdentifier() === $field;

                    return;
                }
                $alias = $this->_table->aliasField($field);
                !$check && $check = preg_match(
                    '/^' . $alias . '/',
                    // TODO: Add test to show that cloning is necessary here to avoid issue mentioned in
                    // https://github.com/UseMuffin/Footprint/issues/74
                    $expression->sql(clone $query->getValueBinder())
                );
            });

            $value = Hash::get((array)$options, $path);
            if (!$check && $value) {
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
    protected function _injectEntity(EntityInterface $entity, ArrayObject $options, array $fields): void
    {
        $new = $entity->isNew() !== false;

        foreach ($fields as $field => $when) {
            if (!in_array($when, ['always', 'new', 'existing'])) {
                throw new UnexpectedValueException(sprintf(
                    'When should be one of "always", "new" or "existing". The passed value "%s" is invalid',
                    $when
                ));
            }

            if ($entity->isDirty($field)) {
                continue;
            }

            if (
                $when === 'always' ||
                ($when === 'new' && $new) ||
                ($when === 'existing' && !$new)
            ) {
                $entity->set(
                    $field,
                    Hash::get(
                        $options,
                        $this->getConfig('propertiesMap.' . $field)
                    )
                );
            }
        }
    }
}
