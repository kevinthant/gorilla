<?php
namespace Thant\Gorilla;
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 11:39 AM
 */

interface  IWorker
{
	public function perform($client_id, array $options);
	public static function getName();
}