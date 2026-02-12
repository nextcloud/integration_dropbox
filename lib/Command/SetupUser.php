<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Command;

use OCA\Dropbox\AppInfo\Application;
use OCA\Dropbox\Service\SecretService;
use OCP\Config\IUserConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupUser extends Command {
	public function __construct(
		private SecretService $secretService,
		private IUserConfig $userConfig,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('integration_dropbox:setup-user')
			->addArgument('userId', InputArgument::REQUIRED)
			->addArgument('accountId', InputArgument::REQUIRED)
			->addArgument('refresh_token', InputArgument::REQUIRED)
			->addArgument('access_token', InputArgument::REQUIRED)
			->setDescription('Setup the client credentials');
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->userConfig->setValueString($input->getArgument('userId'), Application::APP_ID, 'account_id', $input->getArgument('accountId'), lazy: true);
			$this->secretService->setEncryptedUserValue($input->getArgument('userId'), 'refresh_token', $input->getArgument('refresh_token'));
			$this->secretService->setEncryptedUserValue($input->getArgument('userId'), 'token', $input->getArgument('access_token'));
		} catch (\Exception $ex) {
			$output->writeln('<error>Failed to setup client credentials</error>');
			$output->writeln($ex->getMessage());
			return 1;
		}

		return 0;
	}
}
