<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\BackgroundJob;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\DropboxStorageAPIService;
use OCP\AppFramework\Utility\ITimeFactory;

use OCP\BackgroundJob\QueuedJob;
use OCP\Config\IUserConfig;

class ImportDropboxJob extends QueuedJob {

	/**
	 * A QueuedJob to partially import dropbox files and launch following job
	 */
	public function __construct(
		ITimeFactory $timeFactory,
		private DropboxStorageAPIService $service,
		private IUserConfig $userConfig,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @param array{user_id: string} $argument
	 * @return void
	 */
	public function run($argument) {
		$userId = $argument['user_id'];
		try {
			$this->service->importDropboxJob($userId);
		} catch (\Exception|\Throwable $e) {
			$this->userConfig->setValueString($userId, Application::APP_ID, 'last_import_error', $e->getMessage(), lazy: true);
		}
	}
}
