<?php
namespace Thant\Gorilla\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Thant\Gorilla\Client;
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 3:52 PM
 */
class Stop extends Command
{
	protected function configure()
	{
		$this
			// the name of the command (the part after "bin/console")
			->setName('gorilla:stop')

			// the short description shown while running "php bin/console list"
			->setDescription('Stop Gorilla Job Server')

			// the full command description shown when running the command with
			// the "--help" option
			->setHelp('This command allows you to stop Gorilla Job Server')
			->addOption('port', 'p',  InputOption::VALUE_REQUIRED, 'Port number on which Gorilla should listen for incoming connection')
			->addOption('secret', 's', InputOption::VALUE_REQUIRED, 'Server secrect key to connect. Leaving it blank will use default key');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$host = 'localhost';
		$port = $input->getOption('port');
		$secret = $input->getOption('secret');

		$client = new Client($secret, $host, $port);
		if($client->stop())
		{
			echo "Sever is stopping now\n";
		}
		else
		{
			echo "Unable to reach to server\n";
		}

	}
}