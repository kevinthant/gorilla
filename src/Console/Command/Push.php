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
class Push extends Command
{
	protected function configure()
	{
		$this
			// the name of the command (the part after "bin/console")
			->setName('gorilla:push')

			// the short description shown while running "php bin/console list"
			->setDescription('Push a job to Gorilla')

			// the full command description shown when running the command with
			// the "--help" option
			->setHelp('This command allows you to push a job to be processed by Gorilla')
			->addOption('port', 'p',  InputOption::VALUE_REQUIRED, 'Port number on which Gorilla should listen for incoming connection')
			->addOption('secret', 's', InputOption::VALUE_REQUIRED, 'Server secrect key to connect. Leaving it blank will use default key')
			->addOption('worker', 'w',  InputOption::VALUE_REQUIRED, 'Name of the work that will process this job')
			->addOption('options', 'o', InputOption::VALUE_OPTIONAL, 'Any optional information you want to pass, separated by comma with equal sign like ini setting');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$host = 'localhost';
		$port = $input->getOption('port');
		$secret = $input->getOption('secret');
		$worker = $input->getOption('worker');
		$opt_str = $input->getOption('options');

		$parts = explode(',', trim($opt_str));

		$options = array();
		foreach($parts as $part)
		{
			list($key, $val) = explode('=', trim($part));
			$options[$key] = $val;
		}

		$client = new Client($secret, $host, $port);
		$status = $client->push($worker, $options);

		echo "Status recieved: ". var_export($status, true) . "\n";
	}
}