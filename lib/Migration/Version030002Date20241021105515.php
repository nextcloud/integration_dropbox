<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Migration;

use Closure;
use OCA\Dropbox\AppInfo\Application;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version030002Date20241021105515 extends SimpleMigrationStep {

	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// migrate api credentials in app config
		foreach (['client_id', 'client_secret'] as $key) {
			$value = $this->config->getAppValue(Application::APP_ID, $key);
			if ($value === '') {
				continue;
			}
			$this->config->setAppValue(Application::APP_ID, $key, $this->crypto->encrypt($value));
		}

		// user tokens
		$qbUpdate = $this->connection->getQueryBuilder();
		$qbUpdate->update('preferences')
			->set('configvalue', $qbUpdate->createParameter('updateValue'))
			->where(
				$qbUpdate->expr()->eq('appid', $qbUpdate->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbUpdate->expr()->eq('userid', $qbUpdate->createParameter('updateUserId'))
			)
			->andWhere(
				$qbUpdate->expr()->eq('configkey', $qbUpdate->createNamedParameter('token', IQueryBuilder::PARAM_STR))
			);

		$qbSelect = $this->connection->getQueryBuilder();
		$qbSelect->select('userid', 'configvalue')
			->from('preferences')
			->where(
				$qbSelect->expr()->eq('appid', $qbSelect->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('token', IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbSelect->expr()->nonEmptyString('configvalue')
			)
			->andWhere(
				$qbSelect->expr()->isNotNull('configvalue')
			);
		$req = $qbSelect->executeQuery();
		while ($row = $req->fetch()) {
			$userId = $row['userid'];
			$storedClearToken = $row['configvalue'];
			$encryptedToken = $this->crypto->encrypt($storedClearToken);
			$qbUpdate->setParameter('updateValue', $encryptedToken, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateUserId', $userId, IQueryBuilder::PARAM_STR);
			$qbUpdate->executeStatement();
		}
		$req->closeCursor();

		// user refresh tokens

		$qbUpdate = $this->connection->getQueryBuilder();
		$qbUpdate->update('preferences')
			->set('configvalue', $qbUpdate->createParameter('updateValue'))
			->where(
				$qbUpdate->expr()->eq('appid', $qbUpdate->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbUpdate->expr()->eq('userid', $qbUpdate->createParameter('updateUserId'))
			)
			->andWhere(
				$qbUpdate->expr()->eq('configkey', $qbUpdate->createNamedParameter('refresh_token', IQueryBuilder::PARAM_STR))
			);

		$qbSelect = $this->connection->getQueryBuilder();
		$qbSelect->select('userid', 'configvalue')
			->from('preferences')
			->where(
				$qbSelect->expr()->eq('appid', $qbSelect->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('refresh_token', IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbSelect->expr()->nonEmptyString('configvalue')
			)
			->andWhere(
				$qbSelect->expr()->isNotNull('configvalue')
			);
		$req = $qbSelect->executeQuery();
		while ($row = $req->fetch()) {
			$userId = $row['userid'];
			$storedClearToken = $row['configvalue'];
			$encryptedToken = $this->crypto->encrypt($storedClearToken);
			$qbUpdate->setParameter('updateValue', $encryptedToken, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateUserId', $userId, IQueryBuilder::PARAM_STR);
			$qbUpdate->executeStatement();
		}
		$req->closeCursor();
	}
}
