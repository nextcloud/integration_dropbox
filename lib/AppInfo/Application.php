<?php
/**
 * Nextcloud - Dropbox
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Dropbox\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\Notification\IManager as INotificationManager;

use OCA\Dropbox\Notification\Notifier;

/**
 * Class Application
 *
 * @package OCA\Dropbox\AppInfo
 */
class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_dropbox';
	public const DEFAULT_DROPBOX_CLIENT_ID = 'hh276c3kzellh2x';
	public const DEFAULT_DROPBOX_CLIENT_SECRET = 'rdsuw9qg4y4fj5p';

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$manager = $container->get(INotificationManager::class);
		$manager->registerNotifierService(Notifier::class);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
	}
}
