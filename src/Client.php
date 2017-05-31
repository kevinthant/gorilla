<?php
/**
 * Created by PhpStorm.
 * User: kthant
 * Date: 5/31/2017
 * Time: 3:38 PM
 */

namespace Thant\Gorilla;

use Net_Socket;
use Thant\Gorilla\JobServer\Handler;
use Exception;

/**
 * Description of Client
 *
 * @author kevin
 */
class Client extends Net_Socket
{
	//put your code here
	private $secretKey;
	public  $host;
	public  $port;
	private $debug     = false;
	private $debugMode = 'text';
	private $debugDest = 'stdout';

	public function __construct($secretKey, $host, $port)
	{
		$this->secretKey = $secretKey;
		$this->host = $host;
		$this->port = $port;
		$this->blocking = false;
	}

	public function setDebug($flag = false)
	{
		$this->debug = $flag;
	}

	public function setDebugMode($mode = 'html')
	{
		$this->debugMode = $mode;
	}

	public function setDebugDestination($dest = 'stdout')
	{
		$this->debugDest = $dest;
	}

	public function stop()
	{

		$this->connect($this->host, $this->port, false, 5);
		// Send data including linebreak
		$data = array('secret' => $this->secretKey, 'command' => Handler::CMD_STOP);
		$data = json_encode($data);

		$this->writeLine($data);

		$this->_sendDebugMessage("Command sent: " . $data);

		// receive data until linebreak
		$result = $this->readLine();
		$this->_sendDebugMessage("Response received: " . $result);

		if($result == null)
		{
			$this->_sendDebugMessage("Server is busy");
		}

		if($result == null)
		{
			return 0;
		}

		// close connection
		$this->disconnect();
		$this->_sendDebugMessage("Disconnected");

		$result = json_decode($result, true);

		if($result['valid'] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	public function push($worker, array $options)
	{

		$this->connect($this->host, $this->port, false, 2);
		// Send data including linebreak
		$data = array('secret' => $this->secretKey,
		              'worker'  => $worker,
		              'command' => Handler::CMD_PUSH,
		              'options' => $options);

		$data = json_encode($data);

		$this->writeLine($data);

		$this->_sendDebugMessage("Command sent: " . $data);

		// receive data until linebreak
		$result = $this->readLine();
		$this->_sendDebugMessage("Response received: " . $result);

		if($result == null)
		{
			$this->_sendDebugMessage("Server is busy");
		}

		if($result == null)
		{
			return 0;
		}

		if($result == 'not connected')
		{
			return -1;
		}

		// close connection
		$this->disconnect();
		$this->_sendDebugMessage("Disconnected");
		try
		{
			$result = json_decode($result, true);
		}
		catch(Exception $e)
		{
			error_log('Unable to parse the daemon return output: ' . $result);
			$this->_sendDebugMessage('Unable to parse the daemon return output: ' . $result);
			return false;
		}

		if($result['valid'] == 1)
		{
			return $result['status'];
		}
		else
		{
			error_log('Error in sending request to deployment daemon: ' . $result['error']);
			$this->_sendDebugMessage('Error in sending request to deployment daemon: ' . $result['error']);
			return false;
		}
	}

	public function isAlive()
	{
		$this->connect($this->host, $this->port, false, 5);
		// Send data including linebreak
		$data = array('secret' => $this->secretKey, 'command' => 'ping');
		$data = json_encode($data);

		$this->writeLine($data);

		$this->_sendDebugMessage("Command sent: " . $data);

		// receive data until linebreak
		$result = $this->readLine();
		$this->_sendDebugMessage("Response received: " . $result);

		if($result == null)
		{
			return true; //Server is busy, that means it is alive
		}

		if($result == 'not connected')
		{
			return false; //Server is stopped
		}

		// close connection
		$this->disconnect();
		$this->_sendDebugMessage("Disconnected");

		$result = json_decode($result, true);

		if($result['valid'] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function _sendDebugMessage($msg)
	{

		if(!$this->debug)
		{
			return false;
		}

		$msg = date("Y-m-d H:i:s", time()) . " " . $msg;

		switch($this->debugMode)
		{
			case    "text":
				$msg = $msg . "\n";
				break;
			case    "html":
				$msg = htmlspecialchars($msg) . "<br />\n";
				break;
		}

		if($this->debugDest == "stdout" || empty($this->debugDest))
		{
			echo $msg;
			flush();
			return true;
		}

		error_log($msg, 3, $this->_debugDest);
		return true;
	}

}

