<?php
namespace Thant\Gorilla\Worker;
use Thant\Gorilla\IQue;
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
		ob_start();

		echo "DUMPER: request from #$client_id:  ". var_export($options, true) . "\n";

		$output = ob_get_contents();
		ob_end_clean();

		echo $output;
		return array(IQue::QUE_STATUS_COMPLETED, $output);
	}

	public static function getName()
	{
		return self::NAME;
	}
}