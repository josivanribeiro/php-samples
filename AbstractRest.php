<?php

abstract class AbstractRest {
		
	private $httpStatusCode;
	
	protected function __construct() {
		
	}
	
	public function getHttpStatusCode () {
		return $this->httpStatusCode;
	}
	
	private function setHttpStatusCode ($httpStatusCode) {
		$this->httpStatusCode = $httpStatusCode;
	}
	
	/**
	 * Executes the call to web service via method Get (Rest API).
	 * 
	 * @param unknown $url the url.
	 * @param unknown $data the data array.
	 * @throws WebServiceException
	 * @return unknown $result the result.
	 */
	protected function executeGet ($url, $data) {
		$result = null;				
		if (!is_null ($data))  {
			$url = $url . '?' . http_build_query ($data);
		}
		echo ("\n\nurl: " . $url . "\n\n");
		try {
			$curl = curl_init ($url);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($curl, CURLOPT_HTTPHEADER,
				array ($this->getAuthHeader())
			);
			$result = curl_exec ($curl);
			$status = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
			echo ("\n\nHttp code status: " . $status . "\n\n");
			$this->setHttpStatusCode ($status);
			curl_close ( $curl );
			echo ("\nThe Rest web service call was successful via Get.\n\n");
		} catch (Exception $e) {
			$message = "Rest service call failed and returned an HTTP status of : " . $status . " " . $e->getMessage();
			throw new WebServiceException ( $message );
		}
		return $result;
	}
	
	/**
	 * Executes the call to web service via method Post (Rest API).
	 * 
	 * @param unknown $url the url.
	 * @param unknown $jsonData the json data.
	 * @throws WebServiceException
	 * @return unknown $result the result.
	 */
	protected function executePost ($url, $jsonData) {
		$result = null;	
		echo ("\n\nurl: " . $url . "\n\n");
		try {
			$curl = curl_init ( $url );
			curl_setopt ($curl, CURLOPT_POST, 1);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($curl, CURLOPT_HTTPHEADER,
				array ('Content-Type: application/json', $this->getAuthHeader())					    
			);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec ($curl);
			$status = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
			
			echo ("\n\nHttp code status: " . $status . "\n\n");
			
			$this->setHttpStatusCode ($status);
			curl_close ($curl);
			echo ("\nThe Rest web service call was successful via Post.\n\n");
		} catch (Exception $e) {
			$message = "Rest service call failed and returned an HTTP status of : " . $status . " " . $e->getMessage();
			throw new WebServiceException ( $message );
		}		
		return $result;
	}

	/**
	 * Gets the rest header authentication credentials.
	 * 
	 * @return string the header auth credentials.
	 */
	private function getAuthHeader () {
		$auth = 'Authorization: system_login="' . Config::get ('rest.auth_system_login') . '", system_pwd="' .Config::get ('rest.auth_system_pwd') . '"';
		return $auth;
	}
	
}

?>
