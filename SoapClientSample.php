<?php

class SoapClientSample extends SoapClient {

	private $log;
		
	const ONE_WAY_OFF = 0;
	
	const ACTION_SERVICE1    = "Service1";
		
	public function __construct () {
		$this->log = Logger::getLogger (__CLASS__);
		try {
			parent::__construct ( null, array (
									'location' => Config::get ('wsdl.location'),
									'uri'      => Config::get ('wsdl.uri'),
									'trace'    => 1,
									'use'      => SOAP_LITERAL
								));		
			
		} catch (SoapFault $fault) {
			$message = "Could not connect to the SOAP Server. SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";
			$this->log->error ($message);
			throw new Exception ($message);			
		}		
	}
	
	/**
	 * Call the service1 service.
	 *
	 * @param unknown $xmlRequest the xml request.
	 * @return mixed the service response.
	 */
	public function service1  ($xmlRequest) {
		$this->log->info ("Starting " . __METHOD__);
		$response = null;
		try {
			
			$response = $this->doRequest ($xmlRequest, self::ACTION_SERVICE1);
			
			if (is_null ($response)) {
				$erroMessage = "null soap response on Service1 call";
				$this->log->error ($erroMessage);
			}			
				
		} catch (Exception $e) {
			$message = "Could not perform a soap request to the service1 service. " . $e->getMessage();
			$this->log->error ($message);
			throw new Exception ($message);
		}
		$this->log->info ("Finishing " . __METHOD__);
		return $response;
	}
	
	

    /**
     * Performs a custom SOAP request.
     *
     * @param unknown $request the request.
     * @param unknown $action the action.
     * @throws Exception
     * @return string the service response.
     */
    private function doRequest ($request, $action) {
    	$this->log->info ("Starting " . __METHOD__);
    	$response = null;
    	try {
    			
    		$response = parent::__doRequest ($request, Config::get ('wsdl.location'), $action, SOAP_1_1, self::ONE_WAY_OFF);
    		$this->log->info ($response);
    			
    	} catch (Exception $e) {
    		$message = "Could not perform a soap request to the Web Service. " . $e->getMessage();
    		$this->log->error ($message);
    		throw new Exception ($message);
    	}
    	$this->log->info ("Finishing " . __METHOD__);
    	return $response;
    }
    
	
}
