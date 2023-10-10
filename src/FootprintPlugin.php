<?php
declare(strict_types=1);

namespace Muffin\Footprint;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Muffin\Footprint\Event\FootprintListener;

class FootprintPlugin extends BasePlugin
{
    protected ?string $name = 'Footprint';

    /**
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * @var \Muffin\Footprint\Event\FootprintListener|null
     */
    protected static ?FootprintListener $listener = null;

    /**
     * Bootstrap hook
     *
     * @param \Cake\Core\PluginApplicationInterface $app Application instance.
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        $app->getEventManager()->on(
            'Model.initialize',
            /** @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event */
            function (EventInterface $event): void {
                $event->getSubject()->getEventManager()->on(static::getListener());
            }
        );
    }

    /**
     * Get footprint listener
     *
     * @return \Muffin\Footprint\Event\FootprintListener
     */
    public static function getListener(): FootprintListener
    {
        return static::$listener ??= new FootprintListener();
    }
}
