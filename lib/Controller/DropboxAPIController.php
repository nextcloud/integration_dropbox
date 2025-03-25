<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Controller;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\DropboxStorageAPIService;
use OCA\Dropbox\Service\SecretService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;

use OCP\IConfig;
use OCP\IRequest;

class DropboxAPIController extends Controller {

	private string $accessToken;
	private string $refreshToken;
	private string $clientID;
	private string $clientSecret;

	public function __construct(string $appName,
		IRequest $request,
		private IConfig $config,
		private DropboxStorageAPIService $dropboxStorageApiService,
		private ?string $userId,
		private SecretService $secretService,
	) {
		parent::__construct($appName, $request);
		$this->accessToken = $this->secretService->getEncryptedUserValue($this->userId, 'token');
		$this->refreshToken = $this->secretService->getEncryptedUserValue($this->userId, 'refresh_token');
		$this->clientID = $this->secretService->getEncryptedAppValue('client_id');
		$this->clientSecret = $this->secretService->getEncryptedAppValue('client_secret');
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getStorageSize(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->accessToken === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		$result = $this->dropboxStorageApiService->getStorageSize(
			$this->accessToken, $this->refreshToken, $this->clientID, $this->clientSecret, $this->userId
		);

		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_UNAUTHORIZED);
		}

		return new DataResponse($result);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function importDropbox(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->accessToken === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		$result = $this->dropboxStorageApiService->startImportDropbox($this->userId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_UNAUTHORIZED);
		}

		return new DataResponse($result);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getImportDropboxInformation(): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse([
			'last_import_error' => $this->config->getUserValue($this->userId, Application::APP_ID, 'last_import_error', '') !== '',
			'dropbox_import_running' => $this->config->getUserValue($this->userId, Application::APP_ID, 'dropbox_import_running') === '1',
			'importing_dropbox' => $this->config->getUserValue($this->userId, Application::APP_ID, 'importing_dropbox') === '1',
			'last_dropbox_import_timestamp' => (int)$this->config->getUserValue($this->userId, Application::APP_ID, 'last_dropbox_import_timestamp', '0'),
			'nb_imported_files' => (int)$this->config->getUserValue($this->userId, Application::APP_ID, 'nb_imported_files', '0'),
		]);
	}
}
