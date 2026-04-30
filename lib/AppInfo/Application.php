<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {

    public const APP_ID = 'dlhgshows';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register services, listeners, etc. here
    }

    public function boot(IBootContext $context): void {
        // Boot logic here
    }
}
