<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Service;

use OCA\Dropbox\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\IUserManager;
use OCP\PreConditionNotMetException;

class SecretService {
	public function __construct(
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
	) {
	}

	/**
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 * @return void
	 * @throws PreConditionNotMetException
	 */
	public function setEncryptedUserValue(string $userId, string $key, string $value): void {
		if ($value === '') {
			$this->userConfig->setValueString($userId, Application::APP_ID, $key, '', lazy: true, flags: IUserConfig::FLAG_SENSITIVE);
			return;
		}
		$this->userConfig->setValueString($userId, Application::APP_ID, $key, $value, lazy: true, flags: IUserConfig::FLAG_SENSITIVE);
	}

	/**
	 * @param string $userId
	 * @param string $key
	 * @return string
	 * @throws \Exception
	 */
	public function getEncryptedUserValue(string $userId, string $key): string {
		return $this->userConfig->getValueString($userId, Application::APP_ID, $key, lazy: true);
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function setEncryptedAppValue(string $key, string $value): void {
		$this->appConfig->setAppValueString($key, $value, lazy: true, sensitive: true);
	}

	/**
	 * @param string $key
	 * @return string
	 * @throws \Exception
	 */
	public function getEncryptedAppValue(string $key): string {
		return $this->appConfig->getAppValueString($key, lazy: true);
	}
}
