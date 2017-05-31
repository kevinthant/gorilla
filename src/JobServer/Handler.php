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

	const LOG_INFO   = 1;
	const CMD_PUSH   = 'push';
	const CMD_STATUS = 'status';
	const CMD_STOP   = 'stop';
	const CMD_PING   = 'ping';

	protected $workers = array();
	protected $configs = array();
	protected $que     = null;
	protected $secret = null;

	public function __construct(IQue $que, array $configs = array())
	{
		$this->configs = $configs;
		$configs = array_merge(['secret' => 'abcdefg'], $configs);
		$this->secret = $configs['secret'];

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

			$data = json_decode($data, true);

			if(!isset($data['secret']))
			{
				throw new Exception('No valid secret key is provided');
			}

			if(strcmp($data['secret'], $this->secret) !== 0)
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
					if(isset($data['id']) && ($item = $this->que->getItem($data['id'])) !== FALSE)
					{
						$this->sendData($clientId, array(
							'status' => $item['status']
						));
					}
					else
					{
						$this->sendData($clientId, null);
					}
					break;

				case self::CMD_STOP:
					$response = new stdClass();
					$response->valid = 1;
					$response->message = 'About to shutdown the server';
					$this->sendData($clientId, $response);
					$this->_server->shutDown();
					break;

				case self::CMD_PING:
					$this->sendData($clientId, [
						'message' => 'PING received and acknowledged',
					  'valid' => 1
					]);

					break;

				default:
					throw new Exception('Invalid command: ' . $data['command'] . ' is given');
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
		$response->valid = 1;
		$worker = $data['worker'];
		$options = $data['options'];

		$outstanding_items = $this->que->getOutstandingItems();

		if(empty($outstanding_items))
		{
			$response->status = IQue::QUE_STATUS_STARTED;
		}
		else
		{
			$response->status = IQue::QUE_STATUS_PENDING;
		}

		$this->que->addItem(array(
			'id'            => $options['id'],
			'worker'        => $worker,
			'options'       => $options,
			'job_client_id' => $clientId,
			'status'        => $response->status,
			'log'           => null
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
		$this->processQue();
	}

	private function sendData($clientId, $data)
	{
		$this->_server->sendData($clientId, json_encode($data) . "\n");
	}

	protected function log($msg, $log_type = self::LOG_INFO)
	{
		$this->_server->_sendDebugMessage($msg);
	}
}