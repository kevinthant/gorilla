<?php
namespace Thant\Gorilla\Worker;
use Thant\Gorilla\IWorker;

/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 11:41 AM
 */
class Dumper implements IWorker
{

	const NAME = 'DUMPER';

	public function perform($client_id, array $options)
	{
		echo "DUMPER: request from #$client_id:  ". var_export($options, true) . "\n";
	}

	public static function getName()
	{
		return self::NAME;
	}
}