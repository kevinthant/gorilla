<?php
namespace Thant\Gorilla;


use Thant\Gorilla\JobServer\Handler;
use Net_Server;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JobServer
 *
 * @author kevin
 */
class JobServer
{

	protected static $server = null;

	public static function start($host, $port, Handler $handler, $debug = false)
	{

		if(self::$server == null)
		{
			// create a server that forks new processes
			$server = &Net_Server::create('sequential', $host, $port);
			$server->_debug = $debug;

			if($debug)
			{
				$server->setDebugMode('text', '/tmp/gorilla.log');
			}

			if(\PEAR::isError($server))
			{
				error_log($server->getMessage());
				$server->_sendDebugMessage($server->getMessage());
			}

			// you won't need this in most cases.
			$server->setIdleTimeout(5);

			// hand over the object that handles server events
			$server->setCallbackObject($handler);
			$server->setMaxClients(1);
			//$server->readBufferSize = 1;
			//$server->readEndCharacter = "\n";

			// start the server
			$server->start();
			self::$server = $server;
		}
	}

}