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

use OCP\BackgroundJob\QueuedJob;
use OCP\AppFramework\Utility\ITimeFactory;

use OCA\Dropbox\Service\DropboxAPIService;

class ImportDropboxJob extends QueuedJob {

	private $jobList;

	/**
	 * A QueuedJob to partially import dropbox files and launch following job
	 *
	 */
	public function __construct(ITimeFactory $timeFactory,
								DropboxAPIService $service) {
		parent::__construct($timeFactory);
		$this->service = $service;
	}

	public function run($arguments) {
		$userId = $arguments['user_id'];
		$this->service->importDropboxJob($userId);
	}
}
