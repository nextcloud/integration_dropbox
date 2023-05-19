<?php
/**
 * Nextcloud - dropbox
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Dropbox\Controller;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\DropboxStorageAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
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
		private ?string $userId) {
		parent::__construct($appName, $request);
		$this->accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$this->refreshToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'refresh_token');
		$this->clientID = $this->config->getAppValue(Application::APP_ID, 'client_id', Application::DEFAULT_DROPBOX_CLIENT_ID);
		$this->clientID = $this->clientID ?: Application::DEFAULT_DROPBOX_CLIENT_ID;
		$this->clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret', Application::DEFAULT_DROPBOX_CLIENT_SECRET);
		$this->clientSecret = $this->clientSecret ?: Application::DEFAULT_DROPBOX_CLIENT_SECRET;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getStorageSize(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->accessToken === '') {
			return new DataResponse([], 400);
		}
		$result = $this->dropboxStorageApiService->getStorageSize(
			$this->accessToken, $this->refreshToken, $this->clientID, $this->clientSecret, $this->userId
		);

		if (isset($result['error'])) {
			return new DataResponse($result['error'], 401);
		}

		return new DataResponse($result);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function importDropbox(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->accessToken === '') {
			return new DataResponse([], 400);
		}
		$result = $this->dropboxStorageApiService->startImportDropbox($this->userId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], 401);
		}

		return new DataResponse($result);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getImportDropboxInformation(): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse([], 400);
		}
		return new DataResponse([
			'importing_dropbox' => $this->config->getUserValue($this->userId, Application::APP_ID, 'importing_dropbox') === '1',
			'last_dropbox_import_timestamp' => (int) $this->config->getUserValue($this->userId, Application::APP_ID, 'last_dropbox_import_timestamp', '0'),
			'nb_imported_files' => (int) $this->config->getUserValue($this->userId, Application::APP_ID, 'nb_imported_files', '0'),
		]);
	}
}
