<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#submitAccessCode', 'url' => '/access-code', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'dropboxAPI#getStorageSize', 'url' => '/storage-size', 'verb' => 'GET'],
		['name' => 'dropboxAPI#importDropbox', 'url' => '/import-files', 'verb' => 'GET'],
		['name' => 'dropboxAPI#getImportDropboxInformation', 'url' => '/import-files-info', 'verb' => 'GET'],
	]
];
