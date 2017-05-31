<?php
namespace Thant\Gorilla\Console\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Thant\Gorilla\JobServer;
use Thant\Gorilla\JobServer\Handler;
use Thant\Gorilla\Que\ArrayQue;
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 3:52 PM
 */
class Start extends SymfonyCommand
{
	protected function configure()
	{
		$this
			// the name of the command (the part after "bin/console")
			->setName('gorilla:start')

			// the short description shown while running "php bin/console list"
			->setDescription('Start Gorilla Job Server')

			// the full command description shown when running the command with
			// the "--help" option
			->setHelp('This command allows you to start Gorilla Job Server')
			->addOption('port', 'p',  InputOption::VALUE_REQUIRED, 'Port number on which Gorilla should listen for incoming connection')
			->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Enable debugging or not')
			->addOption('secret', 's', InputOption::VALUE_OPTIONAL, 'Server secrect key to connect. Leaving it blank will use default key')
			->addOption('workers', 'w', InputOption::VALUE_REQUIRED, 'List of workers to be registered');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$input->validate();

		$host = 'localhost';
		$port = $input->getOption('port');
		$debug = $input->getOption('debug');
		$secret = $input->getOption('secret');

		$configs = array();

		if(trim($secret) !== '')
		{
			$configs['secret'] = $secret;
		}


		$handler = new Handler(new ArrayQue(), $configs);

		$workers = explode(',', $input->getOption('workers'));

		foreach($workers as $worker)
		{
			$worker = trim($worker);
			$handler->registerWorker(new $worker());
		}


		JobServer::start($host, $port, $handler, empty($debug) ? false : true);

	}
}