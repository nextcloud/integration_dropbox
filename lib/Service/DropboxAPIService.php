<?php
/**
 * Nextcloud - dropbox
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Dropbox\Service;

use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use OCA\Dropbox\AppInfo\Application;

class DropboxAPIService {

	private $l10n;
	private $logger;

	/**
	 * Service to make requests to Dropbox API
	 */
	public function __construct (string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IRootFolder $root,
								IConfig $config,
								IClientService $clientService) {
		$this->appName = $appName;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->config = $config;
		$this->root = $root;
		$this->clientService = $clientService;
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $accessToken
	 * @param string $userId
	 * @return array
	 */
	public function getStorageSize(string $accessToken, string $refreshToken, string $clientID, string $clientSecret, string $userId): array {
		// dropbox storage size
		$params = [];
		$result = $this->request($accessToken, $refreshToken, $clientID, $clientSecret, $userId, 'users/get_space_usage', $params, 'POST');
		if (isset($result['error']) || !isset($result['used'])) {
			return $result;
		}
		$info = [
			'usageInStorage' => $result['used'],
		];

		// count files
		$nbFiles = 0;
		$params = [
			// 'limit' => 1,
			'path' => '',
			'recursive' => true,
			'include_media_info' => false,
			'include_deleted' => false,
			'include_has_explicit_shared_members' => false,
			'include_mounted_folders' => true,
			'include_non_downloadable_files' => false,
		];
		$result = $this->request(
			$accessToken, $refreshToken, $clientID, $clientSecret, $userId, 'files/list_folder', $params, 'POST'
		);
		file_put_contents('/tmp/a', json_encode($result));
		if (isset($result['entries']) && is_array($result['entries'])) {
			foreach ($result['entries'] as $entry) {
				if (isset($entry['.tag']) && $entry['.tag'] === 'file') {
					$nbFiles++;
				}
			}
		}
		while (isset($result['has_more'], $result['cursor']) && $result['has_more']) {
			error_log('YEYEYEYEEYEYEYEY');
			$params = [
				'cursor' => $result['cursor'],
			];
			$result = $this->request(
				$accessToken, $refreshToken, $clientID, $clientSecret, $userId, 'files/list_folder/continue', $params, 'POST'
			);
			if (isset($result['error'])) {
				return $result;
			}
			if (isset($result['entries']) && is_array($result['entries'])) {
				foreach ($result['entries'] as $entry) {
					if (isset($entry['.tag']) && $entry['.tag'] === 'file') {
						$nbFiles++;
					}
				}
			}
		}
		$info['nbFiles'] = $nbFiles;

		// free space
        $userFolder = $this->root->getUserFolder($userId);
        $freeSpace = $userFolder->getStorage()->free_space('/');
		$info['freeSpace'] = $freeSpace;
		return $info;
	}

	/**
	 * @param string $accessToken
	 * @param string $refreshToken
	 * @param string $clientID
	 * @param string $clientSecret
	 * @param string $endPoint
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	public function request(string $accessToken, string $refreshToken, string $clientID, string $clientSecret, string $userId,
							string $endPoint, array $params = [], string $method = 'GET'): array {
		try {
			$url = 'https://api.dropboxapi.com/2/' . $endPoint;
			$options = [
				'headers' => [
					'Authorization' => 'Bearer ' . $accessToken,
					'User-Agent' => 'Nextcloud Dropbox integration',
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['headers']['Content-Type'] = 'application/json';
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$response = $e->getResponse();
			if ($response->getStatusCode() === 401) {
				$this->logger->info('Trying to REFRESH the access token', ['app' => $this->appName]);
				// try to refresh the token
				$result = $this->requestOAuthAccessToken($clientID, $clientSecret, [
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken,
				], 'POST');
				if (isset($result['access_token'])) {
					$this->logger->info('Dropbox access token successfully refreshed', ['app' => $this->appName]);
					$accessToken = $result['access_token'];
					$this->config->setUserValue($userId, Application::APP_ID, 'token', $accessToken);
					// retry the request with new access token
					return $this->request($accessToken, $refreshToken, $clientID, $clientSecret, $endPoint, $params, $method);
				} else {
					// impossible to refresh the token
					return ['error' => $this->l10n->t('Token is not valid anymore. Impossible to refresh it.') . ' ' . $result['error']];
				}
			}
			$this->logger->warning('Dropbox API error : '.$e->getMessage(), ['app' => $this->appName]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string $clientID
	 * @param string $clientSecret
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	public function requestOAuthAccessToken(string $clientID, string $clientSecret, array $params = [], string $method = 'GET'): array {
		try {
			$url = 'https://api.dropboxapi.com/oauth2/token';
			$options = [
				'headers' => [
					'Authorization' => 'Basic '. base64_encode($clientID. ':' . $clientSecret),
					'User-Agent' => 'Nextcloud Dropbox integration'
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('OAuth access token refused')];
			} else {
				return json_decode($body, true);
			}
		} catch (ClientException $e) {
			$this->logger->warning('Dropbox OAuth error : '.$e->getMessage(), ['app' => $this->appName]);
			$result = ['error' => $e->getMessage()];

			$response = $e->getResponse();
			$body = $response->getBody();
			$decodedBody = json_decode($body, true);
			if (isset($decodedBody['error_description'])) {
				$result['error_description'] = $decodedBody['error_description'];
			}
			return $result;
		}
	}
}
