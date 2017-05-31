<?php
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 11:54 AM
 */

namespace Thant\Gorilla\JobServer;

use Net_Server_Handler;
use Exception;
use Thant\Gorilla\IQue;
use Thant\Gorilla\IWorker;
use stdClass;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JobServer
 *
 * @author kevin
 */
class Handler extends Net_Server_Handler
{

	const LOG_INFO = 1;
	const KEY      = '#$&*&@&@';
	const CMD_PUSH = 'push';
	const CMD_STATUS = 'status';


	protected $workers = array();
	protected $configs = array();
	protected $que = null;

	public function __construct(IQue $que, array $configs = array())
	{
		$this->configs = $configs;
		$this->que = $que;
	}

	public function registerWorker(IWorker $worker)
	{
		$this->workers[$worker->getName()] = $worker;
	}

	public function removeWorker($name)
	{
		if(isset($this->workers[$name]))
		{
			unset($this->workers[$name]);
			return true;
		}

		return false;
	}

	function onConnect($clientId = 0)
	{
		$this->log("$clientId has now connected");
	}

	/**
	 * If the user sends data, send it back to him
	 *
	 * @access   public
	 *
	 * @param    integer $clientId
	 * @param    string  $data
	 *
	 * @return void
	 */
	function onReceiveData($clientId = 0, $data = "")
	{


		try
		{

			$data = json_decode($data);

			if(!isset($data['secret']))
			{
				throw new Exception('No valid secret key is provided');
			}

			if($data['secret'] != self::KEY)
			{
				throw new Exception('No valid secret key is provided');
			}

			if(!isset($data['command']))
			{
				throw new Exception('Command is not provided');
			}

			switch($data['command'])
			{
				case self::CMD_PUSH:
					$this->pushToQue($clientId, $data);
					break;

				case self::CMD_STATUS:

					break;

				default:
					throw new Exception('Invalid command: '. $data['command'] . ' is given');
			}




		}
		catch(Exception $e)
		{
			$response = new stdClass();
			$response->valid = -1;
			$response->error = $e->getMessage();
			$this->sendData($clientId, $response);
		}
	}


	protected function pushToQue($clientId, array $data)
	{

		if(!isset($data['worker']) || !array_key_exists($data['worker'], $this->workers))
		{
			throw new Exception('Invalid worker or worker is not registered yet.');
		}

		if(!isset($data['options']) || !isset($data['options']['id']))
		{
			throw new Exception('Options parameter is missing');
		}

		$response = new stdClass();
		$worker = $data['worker'];
		$options = $data['options'];

		$outstanding_items = $this->que->getOutstandingItems();


		if(empty($outstanding_items))
		{
			$response->status = IQue::QUE_STATUS_STARTED;
		}
		else{
			$response->status = IQue::QUE_STATUS_PENDING;
		}

		$this->que->addItem(array(
			'worker' => $worker,
			'options' => $options,
			'job_client_id' => $clientId,
			'status' => $response->status,
			'log' => null
		));

		$this->sendData($clientId, $response);
		$this->processQue();
	}

	protected function processQue()
	{
		$items = $this->que->getOutstandingItems();

		foreach($items as $item)
		{
			/**
			 * @var IWorker $worker
			 */
			$worker = $this->workers[$item['worker']];
			list($status, $output) = $worker->perform($item['job_client_id'], $item['options']);
			$item['status'] = $status;
			$item['log'] = $output;

			$this->que->saveItem($item);
		}
	}

	public function onIdle()
	{

		$this->_server->_sendDebugMessage("I am going to look up if there is any item in job que");
		$model = new Ngashint_Model_EnvironmentReleaseHistory();
		$queue = $model->search(array('id'), 'status < 2 AND status >= 0', array('id'));
		if($queue != null)
		{
			$queue = $queue->toArray();;
			$this->processQue($queue);
		}
	}

	/*
	 * @description - this will be working just as, poking the sleeping daemon
	 */
	private function pushRelease($clientId, $params)
	{

		$response = new stdClass();
		$response->valid = 1;
		$response->status = 0; //assume the request item will be in que

		/*
		 *
		$releaseHistory = Ngashint_Model_EnvironmentReleaseHistory::getInstance($params['REHID']);
		if($releaseHistory == null){
				throw new Exception('Invalid REHID '. $params['REHID']);
		}
		$releaseHistory->status = 1; //set the status into "in progress"
		$releaseHistory->save();
		 */

		$model = new Ngashint_Model_EnvironmentReleaseHistory();
		$queue = $model->search(array('id'), 'status < 2 AND status >= 0', array('id'));
		if($queue == null)
		{
			throw new Exception('Deployment queue is empty');
		}

		$queue = $queue->toArray();
		$found = false;
		foreach($queue as $i => $history)
		{
			if($history['id'] == $params['REHID'])
			{
				$found = true;
				if($i == 0)
				{
					$response->status =
						1; //if the request item is the first one in queue, will be processing now, otherwise it is pending or in que
				}
			}
		}

		if($found == false)
		{
			throw new Exception('Invalid REHID supplied');
		}

		$this->sendData($clientId, $response);
		$this->processQue($queue);
	}

	public function build($clientId, $params)
	{
		$response = new stdClass();
		$response->valid = 1;
		$response->status = 0; //assume the request item will be in que

		$model = new Ngashint_Model_BuildQue();
		$where = sprintf('status < %d AND status >= %d', self::QUE_STATUS_COMPLETED, self::QUE_STATUS_PENDING);
		$queue = $model->search(array('id'), $where, array('id'));
		if($queue == null)
		{
			throw new Exception('Build queue is empty');
		}

		$queue = $queue->toArray();
		$found = false;
		foreach($queue as $i => $que)
		{
			if($que['id'] == $params['build_que_id'])
			{
				$found = true;
				if($i == 0)
				{
					$response->status =
						1; //if the request item is the first one in queue, will be processing now, otherwise it is pending or in que
				}
			}
		}

		if($found == false)
		{
			throw new Exception('Invalid build_id supplied');
		}

		$this->sendData($clientId, $response);
		$this->processBuildQue($queue);
	}

	private function processQueOld($queue)
	{

		foreach($queue as $history)
		{

			$historyRow = new Ngashint_ORM_EnvironmentRelease($history['id']);
			$historyRow->status = 1;
			$historyRow->save();

			$environment = new Ngashint_ORM_Environment($historyRow->environment_id);
			//Two possible problems: exception thrown OR build run itself failed
			//if exception thrown, usually it means no recording of this release deployment
			//if build fails, we clearly know it is just FAIL state
			try
			{
				$output = $environment->pushRelease($historyRow->release_id, $history['id']);
				$historyRow->setLog($output);
			}
			catch(Exception $e)
			{
				//Should just delete the record
				$error = $e->getMessage() . "\n" . $e->getTraceAsString();
				error_log($error);
				$historyRow->setLog($error);
			}
		}
	}

	protected function processBuildQue(array $ques)
	{
		foreach($ques as $que)
		{
		}
	}

	private function sendData($clientId, $data)
	{
		$this->_server->sendData($clientId, Zend_Json::encode($data) . "\n");
	}

	protected function log($msg, $log_type = self::LOG_INFO)
	{
		$this->_server->_sendDebugMessage($msg);
	}
}