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
