<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dropbox\Command;

use OCA\Dropbox\Service\SecretService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Setup extends Command {
	public function __construct(
		private SecretService $secretService,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('integration_dropbox:setup')
			->addArgument('client_id', InputArgument::REQUIRED)
			->addArgument('client_secret', InputArgument::REQUIRED)
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
			$this->secretService->setEncryptedAppValue('client_id', $input->getArgument('client_id'));
			$this->secretService->setEncryptedAppValue('client_secret', $input->getArgument('client_secret'));
		} catch (\Exception $ex) {
			$output->writeln('<error>Failed to setup client credentials</error>');
			$output->writeln($ex->getMessage());
			return 1;
		}

		return 0;
	}
}
