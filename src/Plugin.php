<?php
declare(strict_types=1);

namespace Muffin\Footprint;

use Cake\Core\BasePlugin;

/**
 * Plugin for Expose
 */
class Plugin extends BasePlugin
{
    /**
     * @var bool
     */
    protected $middlewareEnabled = false;

    /**
     * @var bool
     */
    protected $bootstrapEnabled = false;

    /**
     * @var bool
     */
    protected $routesEnabled = false;
}
