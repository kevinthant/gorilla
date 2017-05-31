<?php
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 12:26 PM
 */

namespace Thant\Gorilla;

interface IQue
{
	const QUE_STATUS_PENDING   = 0;
	const QUE_STATUS_STARTED   = 1;
	const QUE_STATUS_COMPLETED = 2;

	public function addItem(array $item);
	public function getOutstandingItems();
	public function saveItem($item);
	public function getItem($id);
}