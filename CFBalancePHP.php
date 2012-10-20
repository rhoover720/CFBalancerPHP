<?php
class CFBalancerPHP {
	/* 
	 * Change these as necessary, however we do not recommend running the CFBalance daemon on a remote PC,
	 * as it will report invalid statistics for the local node's CPU / bandwidth utilization.
	 */
	const WEBSERVICE_HOST = "localhost";
	const WEBSERVICE_PORT = 44444;
	
	
	/* API SPECIFICATION ------------------------------------------------------------
	 * The variables below are used to configure the API for the web service daemon.
	 * The expected data should be as follows:
	 * 0,17.download.codefi.re,1.01,8.72,*
	 * 0,21.download.codefi.re,0.91,4.78  
	 * 
	 * The line marked with an asterisk signifies the localhost performance counters.
	 * The data will then be split (on the comma) into an array.
	 * Use the constants below to define the format of this array.
	 * 
	 */
	
	/** Stores timeout for the webservice in seconds
	 */
	const WEBSERVICE_TIMEOUT = 1;
	
	/* Stores which array index contains the time since last check-in
	 */
	const WEBSERVICE_API_EXPIRE = 0;
	
	/** Stores which array index contains the host cname
	 */
	const WEBSERVICE_API_CNAME = 1;

	/** Stores which array index contains the cpu load
	 */
	const WEBSERVICE_API_CPULOAD = 2;
	
	/* Stores which array index contains the net load
	 */
	const WEBSERVICE_API_NETLOAD = 3;
	
	/* Stores which elements contains localhost signifier
	 */
	const WEBSERVICE_API_LOCALHOST_REF = 4;
	
	//
	// END API CONFIG
	//
	
	
	/** Stores the rolling debug log for timing purposes
	 */
	private $debugLog;
	
	/** Emergency variable. True if this function is defunct and should not be used.
	 */
	private $dead;
	
	/** For performance-tracking
	 */
	private $timer;
	
	/** Stores the local node performance counters
	 */
	private $localhost;
	
	//
	// END VARIABLE DECLARATIONS
	//
	
	/** This initializes the CFBalancerPHP class.
	 */
	public function __construct() {
		// Initialize timer value here. This allows for 
		$this->timer = microtime(true);
	}

	/** Returns an array of each server in the CFBalancer pool.
	 *	@return $nodeList contains the server's internal IP, it's public CNAME, it's CPU load average, and it's current outgoing network rate.
	 */
	public function getNodeList() {
		if ($this->dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		$handle = $this->openWebService();
		$nodeListRaw = null;
		
		stream_set_timeout($handle, WEBSERVICE_TIMEOUT);
		
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
		$this->dead = True;
		return null;
	}

	/** Allows us to access data returned as an array from a function.
	 * This is a workaround for php5.3 limitation
	 * @var $array The array to operate on
	 * @var $key The key to return from $array
	 * @return $value of $array at $key 
	 */
	private function getArray(array $array, $key) {
		return $arr[$key];
	}

	/** Processes the raw nodelist, into an array
	 *  @param $raw The raw node list in text form
	 * 	@return $nodeListArray
	 */
	private function processNodeList($raw) {
		//need to add error handing for null NodeList
		if ($raw != NULL && isset($raw) && !empty($raw)) {
			$this->debug(__FUNCTION__ . " - Processing non-null nodeList.");
			$lines = explode("\r\n",$raw);
			//breaks up raw data by the carriage returns into an array by lines
			
			foreach ($lines as $lines) {
				$node = explode(",",$lines);
				if (count($node) == WEBSERVICE_API_LOCALHOST_REF) {
					// stash local performance counter in dedicated variable
					$localhost = $node;
				}
				array_push($nodeList, $node);

				// then breaks up the line data into an array based on 
				// the , we use to seperate data
			}
			return $nodeList;
		}
		else {
			$this->debug(__FUNCTION___ . " ERROR: nodeList was null, empty, or not set.");
			$this->dead = True;
			return null;
		}
	}
	
	/** Checks the pool of servers, determines the most available node, and redirects the client to the new node/$postfix
	 *	@param $postfix	The URL to append to the CNAME to redirect to.
	 */
	public function checkAndRedirect($postfix) {
		if ($this->dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		
		// blah blah blah
		header("Location:");
	}
	
	/**	Performs the comparison of servers in the pool to determine the most available node in the pool.
	 *	@return $redirCNAME the CNAME of the node that is most available
	 */
	public function getRedirectNode() {
		if ($this->dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		// FIXME still need to break up the nodes array into variables to calculate our values to do our math.
		$nodes = $this->getNodeList();
		
		return $redirCNAME;
	}

	/**	Connects to the webservice port that CFBalancer exposes on localhost.
	 *	After connecting to the webservice port, it returns a handle (fp) to that connection.
	 *	@return $webserviceHandle
	 */
	private function openWebservice() {
		// open web service socket.
		// if it takes too long, mark us as $dead, and say where we died.

		$fp = fsockopen(WEBSERVICE_HOST, WEBSERVICE_PORT);
		if(!$fp) {
			$this->debug(__FUCNTION__ . " failed, setting dead.");
			$this->dead = True;
		}
		
		return $webserviceHandle;
	}
	
	/** Saves debug text along with timing data to a buffer for recall with getDebug() later.
	 *  @param $text The text to write to the debug buffer
	 */
	private function debug($text) {
		if ($this->dead) {
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
			echo($this->debugLog);
		} else if (!$this->dead) {
			// If the module isn't dead, tell us quickly and shut up.
			echo("CFBalancePHP: debugging disabled. Nothing to display.");
		} else {
			// However, if we're dead, no matter what, print the damn log!
			echo($this->debugLog);
		}
	}
	
}

?>