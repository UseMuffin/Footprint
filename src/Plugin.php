<?php
declare(strict_types=1);

namespace Muffin\Footprint;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Muffin\Footprint\Event\FootprintListener;

class Plugin extends BasePlugin
{
    /**
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * @var \Muffin\Footprint\Event\FootprintListener|null
     */
    protected static ?FootprintListener $listener;

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
        if (!static::$listener) {
            static::$listener = new FootprintListener();
        }

        return static::$listener;
    }
}
