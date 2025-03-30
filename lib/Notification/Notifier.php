<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Notification;

use InvalidArgumentException;
use OCA\Dropbox\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	public function __construct(
		protected IFactory $factory,
		protected IUserManager $userManager,
		protected INotificationManager $notificationManager,
		protected IURLGenerator $url,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'integration_dropbox';
	}
	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->factory->get('integration_dropbox')->t('Dropbox');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws InvalidArgumentException When the notification was not prepared by a notifier
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'integration_dropbox') {
			// Not my app => throw
			throw new InvalidArgumentException();
		}

		$l = $this->factory->get('integration_dropbox', $languageCode);

		switch ($notification->getSubject()) {
			case 'import_dropbox_finished':
				$p = $notification->getSubjectParameters();
				$nbImported = (int)($p['nbImported'] ?? 0);
				/** @var string $targetPath */
				$targetPath = $p['targetPath'];
				$content = $l->n('%n file was imported from Dropbox storage.', '%n files were imported from Dropbox storage.', $nbImported);

				$notification->setParsedSubject($content)
					->setIcon($this->url->getAbsoluteURL($this->url->imagePath(Application::APP_ID, 'app-dark.svg')))
					->setLink($this->url->linkToRouteAbsolute('files.view.index', ['dir' => $targetPath]));
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new InvalidArgumentException();
		}
	}
}
