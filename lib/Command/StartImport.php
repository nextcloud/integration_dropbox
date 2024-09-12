<?php

namespace OCA\Dropbox\Command;

use OCA\Dropbox\Service\DropboxStorageAPIService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartImport extends Command {
	public function __construct(
		private DropboxStorageAPIService $dropboxStorageAPIService,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('integration_dropbox:start-import')
			->addArgument('user_id', InputArgument::REQUIRED)
			->setDescription('Start import for the passed user');
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
			$this->dropboxStorageAPIService->importDropboxJob($input->getArgument('user_id'));
		} catch (\Exception $ex) {
			$output->writeln('<error>Failed to start import</error>');
			$output->writeln($ex->getMessage());
			return 1;
		}

		return 0;
	}
}
