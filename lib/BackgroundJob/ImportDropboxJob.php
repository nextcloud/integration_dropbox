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
	public function __construct(ITimeFactory $timeFactory,
		DropboxStorageAPIService $service) {
		parent::__construct($timeFactory);
		$this->service = $service;
	}

	public function run($arguments) {
		$userId = $arguments['user_id'];
		$this->service->importDropboxJob($userId);
	}
}
