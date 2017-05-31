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
		$this->que[$item['id']] = $item;
	}

	public function getOutstandingItems()
	{
		return array_filter($this->que, function($item){
			return $item['status'] < IQue::QUE_STATUS_COMPLETED && $item['status'] >= IQue::QUE_STATUS_PENDING;
		});
	}

	public function saveItem($item)
	{
		$this->que[$item['id']] = $item;
	}

	public function getItem($id)
	{
		if(!array_key_exists($id, $this->que))
		{
			return false;
		}
		return $this->que[$id];
	}

}