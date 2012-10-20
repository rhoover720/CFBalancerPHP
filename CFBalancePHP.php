<?php
namespace CFBalancerPHP;
class CFBalancerPHP {
	/**
	 * CodeFire Load Balancer PHP Class Component
	 * 
	 * @author Tyler Montgomery and Randy Hoover
	 */
	
	/** Webservice daemon hostname.
	 * This should be localhost under most cases. Running the service on a remove machine will
	 * skew the results and cause the load calculation to fail.
	 */
	const WEBSERVICE_HOST = "localhost";
	
	/** Webservice daemon port
	 * Change this to the port that you are running the service on
	 */
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
	
	/** Stores which array index contains the time since last check-in
	 */
	const WEBSERVICE_API_EXPIRE = 0;
	
	/** Stores which array index contains the host cname
	 */
	const WEBSERVICE_API_CNAME = 1;

	/** Stores which array index contains the cpu load
	 */
	const WEBSERVICE_API_CPULOAD = 2;
	
	/** Stores which array index contains the net load
	 */
	const WEBSERVICE_API_NETLOAD = 3;
	
	/** Stores which elements contains localhost signifier
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
		// Initialize timer value here. This allows for us to keep track of the performance of the application and diagnose any hangs.
		$this->timer = microtime(true);
	}

	/** Returns an array of each server in the CFBalancer pool.
	 *	@return string contains the server's internal IP, it's public CNAME, it's CPU load average, and it's current outgoing network rate.
	 */
	public function getNodeList() {
		if ($this->dead) {
			$this->debug(__FUNCTION__);
			return null;
		}
		
		$this->debug(__FUNCTION__ . " - Opening webservice.");
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
	 * @param array $array The array to operate on
	 * @param string $key The key to return from $array
	 * @return string value of $array at $key 
	 */
	private function getArray(array $array, $key) {
		return $arr[$key];
	}

	/** Processes the raw nodelist, into an array
	 * @param string $raw The raw node list in text form
	 * @return array Array of nodes other than localhost 
	 */
	private function processNodeList($raw) {
		//FIXME: need to add error handing for null NodeList
		if ($raw != NULL && isset($raw) && !empty($raw)) {
			$this->debug(__FUNCTION__ . " - Processing non-null nodeList.");
			// break up the raw text into lines
			$lines = explode("\r\n",$raw);
			
			foreach ($lines as $lines) {
				// split the raw list into an array per the API specification
				$node = explode(",",$lines);
				
				// check if the count of elements matches the signifier for localhost
				if (count($node) == WEBSERVICE_API_LOCALHOST_REF) {
					// stash local performance counter in dedicated variable
					$localhost = $node;
				}
				
				// store this non-localhost new node in the nodeList
				array_push($nodeList, $node);
			}
			return $nodeList;
		}
		else {
			$this->debug(__FUNCTION___ . " failed, nodeList was null, empty, or not set.");
			$this->dead = True;
			return null;
		}
	}
	
	/** Checks the pool of servers, determines the most available node, and redirects the client to the new node.
	 *  Uses a "Location:" header to accomplish the transfer.
	 *	@param string $postfix	The URL to append to the CNAME to redirect to.
	 */
	public function checkAndRedirect($postfix) {
		if ($this->dead) {
			$this->debug(__FUNCTION__);
			return null;
		}

		header("Location: http://". $this->getRedirectNode() ."/".$postfix);
	}
	
	/**	Performs the comparison of servers in the pool to determine the most available node in the pool.
	 *	@return string the CNAME of the node that is most available
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
	 *	@return resource a handle to the now-open webservice
	 */
	private function openWebservice() {
		// open web service socket.
		// if it takes too long, mark us as $dead, and say where we died.

		$fp = fsockopen(WEBSERVICE_HOST, WEBSERVICE_PORT);
		if(!$fp) {
			$this->debug(__FUCNTION__ . " failed, unable to obtain a file pointer.");
			$this->dead = True;
		}
		
		return $webserviceHandle;
	}
	
	/** Saves debug text along with timing data to a buffer for recall with getDebug() later.
	 * @param string $text The text to write to the debug buffer
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