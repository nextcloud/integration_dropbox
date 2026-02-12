<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Migration;

use Closure;
use OCA\Dropbox\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version040200Date20260211105515 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// migrate api credentials in app config
		foreach (['client_id', 'client_secret'] as $key) {
			$value = $this->appConfig->getAppValueString($key);
			if ($value === '') {
				continue;
			}
			$this->appConfig->deleteAppValue($key);
			$this->appConfig->setAppValueString($key, $this->crypto->decrypt($value), lazy: true, sensitive: true);
		}

		// Make app config entries lazy
		foreach ($this->appConfig->getAppKeys() as $key) {
			if (!$this->appConfig->isLazy($key)) {
				$value = $this->appConfig->getAppValueString($key);
				$this->appConfig->deleteAppValue($key);
				$this->appConfig->setAppValueString($key, $value, lazy: true);
			}
		}

		foreach ($this->userConfig->getUserIds(Application::APP_ID) as $userId) {
			foreach ($this->userConfig->getKeys($userId, Application::APP_ID) as $key) {
				if (!$this->userConfig->isLazy($userId, Application::APP_ID, $key)) {
					$value = $this->userConfig->getValueString($userId, Application::APP_ID, $key);
					$flags = 0;
					if ($key === 'token' || $key === 'refresh_token') {
						$flags = IUserConfig::FLAG_SENSITIVE;
						$value = $this->crypto->decrypt($value);
					}
					$this->userConfig->deleteUserConfig($userId, Application::APP_ID, $key);
					$this->userConfig->setValueString($userId, Application::APP_ID, $key, $value, lazy: true, flags: $flags);
				}
			}
		}
	}
}
