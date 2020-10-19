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

use OCP\App\IAppManager;
use OCP\Files\IAppData;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

use OCP\IRequest;
use OCP\IDBConnection;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\Http\Client\IClientService;

use OCA\Dropbox\Service\DropboxAPIService;
use OCA\Dropbox\AppInfo\Application;

require_once __DIR__ . '/../constants.php';

class ConfigController extends Controller {


    private $userId;
    private $config;
    private $dbconnection;
    private $dbtype;

    public function __construct($AppName,
                                IRequest $request,
                                IServerContainer $serverContainer,
                                IConfig $config,
                                IAppManager $appManager,
                                IAppData $appData,
                                IDBConnection $dbconnection,
                                IURLGenerator $urlGenerator,
                                IL10N $l,
                                LoggerInterface $logger,
                                IClientService $clientService,
                                DropboxAPIService $dropboxAPIService,
                                $userId) {
        parent::__construct($AppName, $request);
        $this->l = $l;
        $this->appName = $AppName;
        $this->userId = $userId;
        $this->appData = $appData;
        $this->serverContainer = $serverContainer;
        $this->config = $config;
        $this->dbconnection = $dbconnection;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->clientService = $clientService;
        $this->dropboxAPIService = $dropboxAPIService;
    }

    /**
     * set config values
     * @NoAdminRequired
     *
     * @param array $values
     * @return DataResponse
     */
    public function setConfig(array $values): DataResponse {
        foreach ($values as $key => $value) {
            $this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
        }
        if (isset($values['user_name']) && $values['user_name'] === '') {
            //// revoke token
            //$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id', DEFAULT_DROPBOX_CLIENT_ID);
            //$clientID = $clientID ?: DEFAULT_DROPBOX_CLIENT_ID;
            //$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret', DEFAULT_DROPBOX_CLIENT_SECRET);
            //$clientSecret = $clientSecret ?: DEFAULT_DROPBOX_CLIENT_SECRET;
            //$accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token', '');
            //$refreshToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'refresh_token', '');

            //$info = $this->dropboxAPIService->request(
            //    $accessToken, $refreshToken, $clientID, $clientSecret, 'auth/token/revoke', [], 'POST'
            //);

            $this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_name');
            $this->config->deleteUserValue($this->userId, Application::APP_ID, 'uid');
            $this->config->deleteUserValue($this->userId, Application::APP_ID, 'account_id');
            $this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
            $this->config->deleteUserValue($this->userId, Application::APP_ID, 'refresh_token');
        }
        $response = new DataResponse(1);
        return $response;
    }

    /**
     * set admin config values
     *
     * @param array $values
     * @return DataResponse
     */
    public function setAdminConfig(array $values): DataResponse {
        foreach ($values as $key => $value) {
            $this->config->setAppValue(Application::APP_ID, $key, $value);
        }
        $response = new DataResponse(1);
        return $response;
    }

    /**
     * @NoAdminRequired
     *
     * @param string $code
     * @return array
     */
    public function submitAccessCode(string $code = ''): DataResponse {
        if ($code === '') {
            $message = $this->l->t('Invalid access code');
            return new DataResponse($message, 400);
        }
        $clientID = $this->config->getAppValue(Application::APP_ID, 'client_id', DEFAULT_DROPBOX_CLIENT_ID);
        $clientID = $clientID ?: DEFAULT_DROPBOX_CLIENT_ID;
        $clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret', DEFAULT_DROPBOX_CLIENT_SECRET);
        $clientSecret = $clientSecret ?: DEFAULT_DROPBOX_CLIENT_SECRET;

        if ($clientID) {
            $result = $this->dropboxAPIService->requestOAuthAccessToken($clientID, $clientSecret, [
                'grant_type' => 'authorization_code',
                'code' => $code,
            ], 'POST');
            if (isset($result['access_token'], $result['refresh_token'])) {
                $accessToken = $result['access_token'];
                $this->config->setUserValue($this->userId, Application::APP_ID, 'token', $accessToken);
                $refreshToken = $result['refresh_token'];
                $this->config->setUserValue($this->userId, Application::APP_ID, 'refresh_token', $refreshToken);
                if (isset($result['uid'], $result['account_id'])) {
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'uid', $result['uid']);
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'account_id', $result['account_id']);
                }
                // get user information
                $data = [];
                $info = $this->dropboxAPIService->request(
                    $accessToken, $refreshToken, $clientID, $clientSecret, 'users/get_current_account', [], 'POST'
                );
                if (isset($info['name'], $info['name']['display_name'])) {
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $info['name']['display_name']);
                    $data['user_name'] = $info['name']['display_name'];
                } else {
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', '??');
                    $data['user_name'] = '??';
                }
                return new DataResponse($data);
            } else {
                $message = $result['error_description'] ?? $result['error'] ?? 'missing token or refresh token';
            }
        } else {
            $message = $this->l->t('Error during OAuth exchanges');
        }
        return new DataResponse($message, 400);
    }
}
