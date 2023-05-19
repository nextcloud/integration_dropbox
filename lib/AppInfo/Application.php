<?php
/**
 * Nextcloud - Dropbox
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Dropbox\AppInfo;

use OCA\Dropbox\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCP\Notification\IManager as INotificationManager;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_dropbox';
	public const DEFAULT_DROPBOX_CLIENT_ID = 'hh276c3kzellh2x';
	public const DEFAULT_DROPBOX_CLIENT_SECRET = 'rdsuw9qg4y4fj5p';
	// consider that a job is not running anymore after N seconds
	public const IMPORT_JOB_TIMEOUT = 3600;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
        $context->registerNotifierService(Notifier::class);
	}

	public function boot(IBootContext $context): void {
	}
}
