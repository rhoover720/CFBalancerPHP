<?php
class CFBalancerPHP {
	/* 
	 * Change these as necessary, however we do not recommend running the CFBalance daemon on a remote PC,
	 * as it will report invalid statistics for the local node's CPU / bandwidth utilization.
	 */
	const WEBSERVICE_HOST = "localhost";
	const WEBSERVICE_PORT = 44444;
	
	// Stores the rolling debug log for timing purposes
	private $debugLog;
	
	// Emergency variable. True if this function is defunct and should not be used.
	private $dead;
	
	// For performance-tracking
	private $timer;
		
	/** This initializes the CFBalancerPHP class.
	 *	We also initialize the $timer variable here as well so we can debug print every step of the way,
	 *	so we can tell if something hangs, or if something takes longer than usual to execute.
	 */
	public function __construct() {
		$timer = microtime(true);
	}

	/** Returns an array of each server in the CFBalancer pool.
	 *	@returns $nodeList contains the server's internal IP, it's public CNAME, it's CPU load average, and it's current outgoing network rate.
	 */
	public function getNodeList() {
		if ($dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		$handle = $this->openWebService();
		$nodeListRaw = null;
		
		// FIXME: add timing code here as well to ensure that the socket never hangs for more than 15ms
		// if it does hang, debug print, and mark us as dead, and return null.
			
		// Ask for webservice to list servers.
		// need to set timeout then make
		//  stream_set_blocking($handle, FALSE) 

		while (!$this->getArray(stream_get_meta_data($fp),"timed_out")) {
			fwrite($handle, "L");
			while (!feof($handle)) {
				$nodeListRaw .= fread($handle, 128);
			}
			$this->debug(__FUNCTION__ . " - Got NodeList in raw form from server.");
			//process raw nodelist into array
			return $this->processNodeList($nodeListRaw);
		}
		//we're dead!
		$this->debug(__FUNCTION__ . " failed, connection timed out. dying.");
		$dead = True;
		return null;
	}

	/** Allows us to access data returned as an array from a function.
	 * This is a workaround for php5.3 limitation
	 * @returns $value of $array at $key 
	 */
	private function getArray(array $array, $key) {
		return $arr[$key];
	}

	/** Processes the raw nodelist, into an array
	 * 	@returns $nodeListArray
	 */
	public function processNodeList($raw) {
		//need to add error handing for null NodeList
		if ($raw != NULL) {
			$this->debug(__FUNCTION__ . " - Processing non-null nodeList.");
			$lines = explode("\r\n",$raw);
			//breaks up raw data by the carriage returns into an array by 
			//lines
			foreach ($lines as $lines) {
				array_push($nodeList, explode(",",$lines));
				//then breaks up the line data into an array based on 
				//the , we use to seperate data
			}
			return $nodeList;
		}
		else {
			$this->debug(__FUNCTION___ . " ERROR: nodeList was null, empty, or not set.");
			$dead = True;
			return null;
		}
	}
	
	/** Checks the pool of servers, determines the most available node, and redirects the client to the new node/$postfix
	 *	@param $postfix	The URL to append to the CNAME to redirect to.
	 */
	public function checkAndRedirect($postfix) {
		if ($dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		
		// blah blah blah
		header("Location:");
	}
	
	/**	Performs the comparison of servers in the pool to determine the most available node in the pool.
	 *	@returns $redirCNAME the CNAME of the node that is most available
	 */
	public function getRedirectNode() {
		if ($dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		
		// blah blah blah
		return $redirCNAME;
	}

	/**	Connects to the webservice port that CFBalancer exposes on localhost.
	 *	After connecting to the webservice port, it returns a handle (fp) to that connection.
	 *	@returns $webserviceHandle
	 */
	private function openWebservice() {
		// open web service socket.
		// if it takes too long, mark us as $dead, and say where we died.

		$fp = fsockopen(WEBSERVICE_HOST, WEBSERVICE_PORT);
		if(!$fp) {
			$this->debug(__FUCNTION__ . " failed, setting dead.");
			$dead = True;
		}
		
		return $webserviceHandle;
	}
	
	/** Saves debug text along with timing data to a buffer for recall with getDebug() later.
	 */
	private function debug($text) {
		if ($dead) {
			$debugLog .= "[ ".microtime(true) - $timer."ms ] DEAD! - ".$text." was called. Ignoring. /r/n";
		} else if (DEBUG) {
			$debugLog .= "[ ".microtime(true) - $timer."ms ] ".$text."/r/n";
		}
	}
	
	/** Prints the debugging buffer.
	 */
	public function debugPrint() {
		if (DEBUG) {
			// If debug is set, echo the log
			echo($debugLog);
		} else if (!$dead) {
			// If the module isn't dead, tell us quickly and shut up.
			echo("CFBalancePHP: debugging disabled. Nothing to display.");
		} else {
			// However, if we're dead, no matter what, print the damn log!
			echo($debugLog);
		}
	}
	
}

?>