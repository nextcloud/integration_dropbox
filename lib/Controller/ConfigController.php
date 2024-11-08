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
use OCA\Dropbox\Service\DropboxAPIService;

use OCA\Dropbox\Service\SecretService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

class ConfigController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private SecretService $secretService,
		private IL10N $l,
		private DropboxAPIService $dropboxAPIService,
		private ?string $userId) {
		parent::__construct($appName, $request);
	}

	/**
	 * set config values
	 *
	 * @param array<string,string> $values
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function setConfig(array $values): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		if (isset($values['user_name']) && $values['user_name'] === '') {
			//// revoke token
			//$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id', Application::DEFAULT_DROPBOX_CLIENT_ID);
			//$clientID = $clientID ?: Application::DEFAULT_DROPBOX_CLIENT_ID;
			//$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret', Application::DEFAULT_DROPBOX_CLIENT_SECRET);
			//$clientSecret = $clientSecret ?: Application::DEFAULT_DROPBOX_CLIENT_SECRET;
			//$accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token', '');
			//$refreshToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'refresh_token', '');

			//$info = $this->dropboxAPIService->request(
			//    $accessToken, $refreshToken, $clientID, $clientSecret, 'auth/token/revoke', [], 'POST'
			//);

			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_name');
			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'uid');
			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'account_id');
			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'email');
			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'refresh_token');
		}
		return new DataResponse(1);
	}

	/**
	 * set admin config values
	 *
	 * @param array<string,string> $values
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	public function setAdminConfig(array $values): DataResponse {
		// currently: client_id and client_secret
		foreach ($values as $key => $value) {
			if ($key === 'client_secret' && $value === 'dummySecret') {
				continue;
			}
			$this->secretService->setEncryptedAppValue($key, $value);
		}
		return new DataResponse(1);
	}

	/**
	 * @param string $code
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function submitAccessCode(string $code = ''): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($code === '') {
			$message = $this->l->t('Invalid access code');
			return new DataResponse($message, Http::STATUS_BAD_REQUEST);
		}
		$clientID = $this->secretService->getEncryptedAppValue('client_id');
		$clientSecret = $this->secretService->getEncryptedAppValue('client_secret');

		$result = $this->dropboxAPIService->requestOAuthAccessToken($clientID, $clientSecret, [
			'grant_type' => 'authorization_code',
			'code' => $code,
		], 'POST');
		if (isset($result['access_token'], $result['refresh_token'])) {
			$accessToken = $result['access_token'];
			$this->secretService->setEncryptedUserValue($this->userId, 'token', $accessToken);
			$refreshToken = $result['refresh_token'];
			$this->secretService->setEncryptedUserValue($this->userId, 'refresh_token', $refreshToken);
			$data = [];
			// get user information
			$info = $this->dropboxAPIService->request(
				$accessToken, $refreshToken, $clientID, $clientSecret, $this->userId, 'users/get_current_account', [], 'POST'
			);
			$data['info'] = $info;
			if (isset($result['account_id'])) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'account_id', $result['account_id']);
				$data['account_id'] = $result['account_id'];
			}
			if (isset($result['email'])) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'email', $result['email']);
				$data['email'] = $result['email'];
			} elseif (isset($info['email'])) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'email', $info['email']);
				$data['email'] = $info['email'];
			}
			if (isset($info['name'], $info['name']['display_name'])) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $info['name']['display_name']);
				$data['user_name'] = $info['name']['display_name'];
			} else {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', '??');
				$data['user_name'] = '??';
			}
			return new DataResponse($data);
		}

		$message = $result['error_description'] ?? $result['error'] ?? 'missing token or refresh token';
		return new DataResponse($message, Http::STATUS_BAD_REQUEST);
	}
}
