<?php

/**
 * Nextcloud - integration_dropbox
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Dropbox\BackgroundJob;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\DropboxStorageAPIService;
use OCP\AppFramework\Utility\ITimeFactory;

use OCP\BackgroundJob\QueuedJob;

class ImportDropboxJob extends QueuedJob {

	/** @var DropboxStorageAPIService */
	private $service;

	/**
	 * A QueuedJob to partially import dropbox files and launch following job
	 *
	 */
	public function __construct(
        ITimeFactory $timeFactory,
		DropboxStorageAPIService $service,
        private IConfig $config
    ) {
		parent::__construct($timeFactory);
		$this->service = $service;
	}

	/**
	 * @param array{user_id: string} $argument
	 * @return void
	 */
	public function run($argument) {
		$userId = $argument['user_id'];
        try {
            $this->service->importDropboxJob($userId);
        }catch(\Exception|\Throwable $e) {
            $this->config->setUserValue($userId, Application::APP_ID, 'last_import_error', $e->getMessage());
        }
	}
}
