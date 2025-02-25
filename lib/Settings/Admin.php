<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Settings;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\SecretService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;

use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private SecretService $secretService,
		private IInitialState $initialStateService,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$clientID = $this->secretService->getEncryptedAppValue('client_id');
		$clientSecret = $this->secretService->getEncryptedAppValue('client_secret');

		$adminConfig = [
			'client_id' => $clientID,
			'client_secret' => $clientSecret !== '' ? 'dummySecret' : ''
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
