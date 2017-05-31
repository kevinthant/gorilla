<?php
namespace Thant\Gorilla\Que;

use Thant\Gorilla\IQue;

/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 12:29 PM
 */
class ArrayQue implements IQue
{

	protected $que = array();

	public function __construct()
	{
	}

	public function addItem(array $item)
	{
		array_push($this->que, $item);
	}

	public function getOutstandingItems()
	{

	}

	public function setItemStatus($status)
	{

	}
}