<?php

class BaseMQ {

	private $log;
	private $connectionName;
    private $channelName;
    private $queueManager;
    private $queue;
    private $errorQueue;
    private $msg;
    private $port;
    private $connection;
    private $obj;
    private $certificateLabel;
    private $keyRepository;   

    public function __construct() {
    	$this->log = Logger::getLogger (__CLASS__);
    	$this->checkExtensionLoaded ();        
    }

    /**
     * Initializes MQ configuration properties.
     */
    protected function initMQ() {
        $this->queueManager = Config::get ( 'mq.queue_manager' );
        $this->connectionName = Config::get ( 'mq.connection_name' );
        $this->channelName = Config::get ( 'mq.encrypted_channel' );
        $this->queue = Config::get ( 'mq.queue_name' );
        $this->errorQueue = Config::get ( 'mq.queue_name_error' );
        $this->port = Config::get ( 'mq.port' );
        $this->certificateLabel = Config::get ( 'mq.certificate_label' );
        $this->keyRepository = Config::get ( 'mq.key_repository' );

        $this->log->info ("\nthis->queueManager " . $this->queueManager);
        $this->log->info ("\nthis->connectionName " . $this->connectionName);
        $this->log->info ("\nthis->channelName " . $this->channelName);
        $this->log->info ("\nthis->queue " . $this->queue);
        $this->log->info ("\nthis->errorQueue " . $this->errorQueue);
        $this->log->info ("\nthis->port " . $this->port);
        $this->log->info ("\nthis->certificateLabel " . $this->certificateLabel);
        $this->log->info ("\nthis->keyRepository " . $this->keyRepository);

        $this->log->info ("\n\n\nMQ properties initialized.\n\n\n");
    }

    /**
     * Puts a message on a MQ queue.
     *
     * @param unknown $msg the message.
     * @param unknown $crtfLabel the certificate label.
     * @param unknown $keyPath the path to ssl key store.
     * @throws Exception
     */
    public function putMessage ($msg) {
        try {
            // connect to queue manager
            $this->getSSLConnection ();

            // $mqods = array('ObjectName' => $queue_name, 'ObjectQMgrName' => $queue_manager);
            $mqods = array (
                'ObjectName' => $this->queue
            );


            // open the connection to the given queue
            mqseries_open ( $this->connection, $mqods, MQSERIES_MQOO_INPUT_AS_Q_DEF | MQSERIES_MQOO_FAIL_IF_QUIESCING | MQSERIES_MQOO_OUTPUT, $this->obj, $comp_code, $reason );

            // $obj now holds the reference to the queue object

            // setup the message descriptor array. Check MQSeries reference manuals
            $md = array (
                'Version' => MQSERIES_MQMD_VERSION_1,
                'Expiry' => MQSERIES_MQEI_UNLIMITED,
                'Report' => MQSERIES_MQRO_NONE,
                'MsgType' => MQSERIES_MQMT_DATAGRAM,
                'Format' => MQSERIES_MQFMT_STRING,
                'Priority' => 1,
                'Persistence' => MQSERIES_MQPER_PERSISTENT
            );

            // setup the put message options
            $pmo = array (
                'Options' => MQSERIES_MQPMO_NEW_MSG_ID | MQSERIES_MQPMO_SYNCPOINT
            );

            // put the message $msg on the queue
            mqseries_put ( $this->connection, $this->obj, $md, $pmo, $msg, $comp_code, $reason );

            if ($comp_code !== MQSERIES_MQCC_OK) {
                printf ( "put CompCode:%d Reason:%d Text:%s<br>\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
                $message = "\n\nCould not put the message in the queue.";
                throw new Exception ( $message );
            } else {
                $this->log->info ("\n\nMessage placed in the queue successfully.\n");
                $this->log->info ("\nFirst 2000 characters of the placed message: \n" . substr ($msg, 0, 1999) . " ...");
            }

            // close the object reference $obj
            $this->closeTarget ();

            // disconnect from the queue manager
            $this->disconnect ();
        } catch ( Exception $e ) {
            $message = "\n\nAn error occurred while putting the message in the queue. " . $e->getMessage ();
            $this->log->error ($message);
            throw new Exception ( $message );
        }
    }

    public function putMsgErrorQueue ($msg) {
        try {
            // connect to queue manager
            $this->getSSLConnection ();

            // $mqods = array('ObjectName' => $queue_name, 'ObjectQMgrName' => $queue_manager);
            $mqods = array (
                'ObjectName' => $this->errorQueue
            );


            // open the connection to the given queue
            mqseries_open ( $this->connection, $mqods, MQSERIES_MQOO_INPUT_AS_Q_DEF | MQSERIES_MQOO_FAIL_IF_QUIESCING | MQSERIES_MQOO_OUTPUT, $this->obj, $comp_code, $reason );

            // $obj now holds the reference to the queue object

            // setup the message descriptor array. Check MQSeries reference manuals
            $md = array (
                'Version' => MQSERIES_MQMD_VERSION_1,
                'Expiry' => MQSERIES_MQEI_UNLIMITED,
                'Report' => MQSERIES_MQRO_NONE,
                'MsgType' => MQSERIES_MQMT_DATAGRAM,
                'Format' => MQSERIES_MQFMT_STRING,
                'Priority' => 1,
                'Persistence' => MQSERIES_MQPER_PERSISTENT
            );

            // setup the put message options
            $pmo = array (
                'Options' => MQSERIES_MQPMO_NEW_MSG_ID | MQSERIES_MQPMO_SYNCPOINT
            );

            // put the message $msg on the queue
            mqseries_put ( $this->connection, $this->obj, $md, $pmo, $msg, $comp_code, $reason );

            if ($comp_code !== MQSERIES_MQCC_OK) {
                printf ( "put CompCode:%d Reason:%d Text:%s<br>\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
                $message = "\n\nCould not put the message in the queue.";
                throw new Exception ( $message );
            } else {
                $this->log->info ("\n\nMessage placed in the queue successfully.\n");
                $this->log->info ("\nFirst 2000 characters of the placed message: \n" . substr ($msg, 0, 1999) . " ...");
            }

            // close the object reference $obj
            $this->closeTarget ();

            // disconnect from the queue manager
            $this->disconnect ();
        } catch ( Exception $e ) {
            $message = "\n\nAn error occurred while putting the message in the error queue. " . $e->getMessage ();
            $this->log->error ($message);
            throw new Exception ( $message );
        }
    }

    /**
     * Retrieves a message from a MQ queue.
     *
     * @throws Exception
     */
    public function readMessage() {
        $msg = null;
        try {
            // connect to queue manager
            $this->getSSLConnection ();

            // $mqods = array('ObjectName' => $queue_name, 'ObjectQMgrName' => $queue_manager);
            $mqods = array (
                'ObjectName' => $this->queue
            );

            // open the connection to the given queue
            mqseries_open ( $this->connection, $mqods, MQSERIES_MQOO_INPUT_AS_Q_DEF | MQSERIES_MQOO_FAIL_IF_QUIESCING | MQSERIES_MQOO_OUTPUT, $this->obj, $comp_code, $reason );

            // $obj now holds the reference to the queue object

            // setup empty message descriptor.
            $mdg = array ();

            // setup get message options
            $gmo = array ('Options' => MQSERIES_MQGMO_FAIL_IF_QUIESCING | MQSERIES_MQGMO_WAIT, 'WaitInterval' => 30000);

            // get the message from the queue
            mqseries_get ( $this->connection, $this->obj, $mdg, $gmo, 4194304, $msg, $data_length, $comp_code, $reason );

            if ($comp_code !== MQSERIES_MQCC_OK) {
                printf ( "Could not read the message from the queue.\n\n CompCode:%d \nReasonCode:%d \nReason:%s\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
                $message = "\n\nCould not read the message from the queue. Reason:".mqseries_strerror ( $reason );
                throw new Exception ( $message );
            } else {
                $this->log->info ("\nMessage read successfully from the queue.\n");
                $this->log->info ("\nFirst 2000 characters of the read message: \n" . substr ($msg, 0, 1999) . " ...");
            }

            // close the object reference $obj
            $this->closeTarget ();

            // disconnect from the queue manager
            $this->disconnect ();
        } catch ( Exception $e ) {
            $message = "\nAn error occurred while reading the message in the queue. " . $e->getMessage () . "\n";
            $this->log->error ($message);
            throw new Exception ( $message );
        }
        return $msg;
    }

    /**
     * Retrieves the messages from a MQ queue.
     *
     * @throws Exception
     */
    public function readMessages() {
        $msgArr = array();
        try {
            // connect to queue manager
            $this->getSSLConnection ();

            // $mqods = array('ObjectName' => $queue_name, 'ObjectQMgrName' => $queue_manager);
            $mqods = array (
                'ObjectName' => $this->queue
            );

            // open the connection to the given queue
            mqseries_open ( $this->connection, $mqods, MQSERIES_MQOO_INPUT_AS_Q_DEF | MQSERIES_MQOO_FAIL_IF_QUIESCING | MQSERIES_MQOO_OUTPUT, $this->obj, $comp_code, $reason );

            // $obj now holds the reference to the queue object
            while ( $comp_code != MQSERIES_MQCC_FAILED ) {

                $msg = null;

                // setup empty message descriptor.
                $mdg = array ();

                // setup get message options
                $gmo = array ('Options' => MQSERIES_MQGMO_FAIL_IF_QUIESCING | MQSERIES_MQGMO_WAIT, 'WaitInterval' => 30000);

                // get the message from the queue
                mqseries_get ( $this->connection, $this->obj, $mdg, $gmo, 4194304, $msg, $data_length, $comp_code, $reason );

                if ($reason != MQSERIES_MQRC_NONE) {
                    if ($reason == MQSERIES_MQRC_NO_MSG_AVAILABLE) {
                        $this->log->info ("No More Messages");
                    } else {
                        printf ( "GET CompCode:%d Reason:%d Text:%s<br>", $comp_code, $reason, mqseries_strerror ( $reason ) );
                        $this->log->info ("\n\nCould not read the message from the queue.");
                    }
                } else {
                    if (empty ( $msg )) {
                        continue;
                    }
                    $msgKey = date ( 'Y-m-d', strtotime ( $mdg ['PutDate'] ) ) . ' ' . $mdg ['PutTime'];
                    $msgArr [$msgKey] = $msg;

                    $this->log->info ("\nMessage read successfully from the queue.\n");
                    $this->log->info ("\nFirst 2000 characters of the read message: \n" . substr ($msg, 0, 1999) . " ...");
                }
            }
            // close the object reference $obj
            $this->closeTarget ();
            // disconnect from the queue manager
            $this->disconnect ();
        } catch ( Exception $e ) {
            $message = "\nAn error occurred while reading the messages in the queue. " . $e->getMessage () . "\n";
            $this->log->error ($message);
            throw new Exception ( $message );
        }
        return $msgArr;
    }
    
    /**
     * Retrieves the error messages from a MQ error queue.
     *
     * @throws Exception
     */
    public function readErrorMessages() {
    	$msgArr = array();
    	try {
    		// connect to queue manager
    		$this->getSSLConnection ();
    
    		// $mqods = array('ObjectName' => $queue_name, 'ObjectQMgrName' => $queue_manager);
    		$mqods = array (
    				'ObjectName' => $this->errorQueue
    		);
    
    		// open the connection to the given queue
    		mqseries_open ( $this->connection, $mqods, MQSERIES_MQOO_INPUT_AS_Q_DEF | MQSERIES_MQOO_FAIL_IF_QUIESCING | MQSERIES_MQOO_OUTPUT, $this->obj, $comp_code, $reason );
    
    		// $obj now holds the reference to the queue object
    		while ( $comp_code != MQSERIES_MQCC_FAILED ) {
    
    			$msg = null;
    
    			// setup empty message descriptor.
    			$mdg = array ();
    
    			// setup get message options
    			$gmo = array ('Options' => MQSERIES_MQGMO_FAIL_IF_QUIESCING | MQSERIES_MQGMO_WAIT, 'WaitInterval' => 30000);
    
    			// get the message from the queue
    			mqseries_get ( $this->connection, $this->obj, $mdg, $gmo, 4194304, $msg, $data_length, $comp_code, $reason );
    
    			if ($reason != MQSERIES_MQRC_NONE) {
    				if ($reason == MQSERIES_MQRC_NO_MSG_AVAILABLE) {
    					$this->log->info ("No More Messages");
    				} else {
    					printf ( "GET CompCode:%d Reason:%d Text:%s<br>", $comp_code, $reason, mqseries_strerror ( $reason ) );
    					$this->log->info ("\n\nCould not read the error message from the error queue.");
    				}
    			} else {
    				if (empty ( $msg )) {
    					continue;
    				}
    				$msgKey = date ( 'Y-m-d', strtotime ( $mdg ['PutDate'] ) ) . ' ' . $mdg ['PutTime'];
    				$msgArr [$msgKey] = $msg;
    
    				$this->log->info ("\nMessage read successfully from the error queue.\n");
    				$this->log->info ("\nFirst 2000 characters of the read message: \n" . substr ($msg, 0, 1999) . " ...");
    			}
    		}
    		// close the object reference $obj
    		$this->closeTarget ();
    		// disconnect from the queue manager
    		$this->disconnect ();
    	} catch ( Exception $e ) {
    		$message = "\nAn error occurred while reading the messages in the error queue. " . $e->getMessage () . "\n";
    		$this->log->error ($message);
    		throw new Exception ( $message );
    	}
    	return $msgArr;
    }

    /**
     * Gets a SSL Connection to MQ.
     *
     * @throws Exception
     */
    private function getSSLConnection() {
        $mqcno = array (
            'Version' => MQSERIES_MQCNO_VERSION_4,
            'Options' => MQSERIES_MQCNO_STANDARD_BINDING,
            'MQCD' => array (
                'Version' => 7, // MQCD_VERSION_7
                'ConnectionName' => $this->getConnectionNameAndPort (),
                'ChannelName' => $this->channelName,
                'TransportType' => MQSERIES_MQXPT_TCP,
                'SSLCipherSpec' => 'TLS_RSA_WITH_AES_128_CBC_SHA256', // available options in http://www-01.ibm.com/support/knowledgecenter/SSFKSJ_7.1.0/com.ibm.mq.doc/ja34740_.htm
                'CertificateLabel' => $this->certificateLabel
            ), // name of certificate on the server

            'MQSCO' => array (
                'KeyRepository' => $this->keyRepository
            )
        ); // path to ssl key store - user running code must have read access


        mqseries_connx ( $this->queueManager, $mqcno, $this->connection, $comp_code, $reason );

        if ($comp_code !== MQSERIES_MQCC_OK) {
            printf ( "Connx CompCode:%d Reason:%d Text:%s<br>\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
            $message = "\nCould not connect to the queue manager.\n";
            $this->log->error ($message);
            throw new Exception ( $message );
        } else {
            $this->log->info ("\nSSL Connection successfully open.\n");
        }
    }

    /**
     * It is the inverse of the mqseries_open() (MQOPEN) call.
     *
     * @throws Exception
     */
    private function closeTarget() {
        mqseries_close ( $this->connection, $this->obj, MQSERIES_MQCO_NONE, $comp_code, $reason );
        if ($comp_code !== MQSERIES_MQCC_OK) {
            printf ( "CLOSE CompCode:%d Reason:%d Text:%s\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
            $message = "\nCould not close the target object.\n";
            $this->log->error ($message);
            throw new Exception ( $message );
        } else {
            $this->log->info ("\nTarget closed successfully.\n");
        }
    }

    /**
     * Breaks the connection between the queue manager and the application.
     *
     * @throws Exception
     */
    private function disconnect() {
        mqseries_disc ( $this->connection, $comp_code, $reason );
        if ($comp_code !== MQSERIES_MQCC_OK) {
            printf ( "disc CompCode:%d Reason:%d Text:%s<br>\n", $comp_code, $reason, mqseries_strerror ( $reason ) );
            $message = "\nCould not disconnect.\n";
            $this->log->error ($message);
            throw new Exception ( $message );
        } else {
            $this->log->info ("\nApplication successfully disconnected.\n");
        }
    }

    /**
     * Checks if the MQ Series extension was correctly loaded.
     *
     * @throws Exception
     */
    private function checkExtensionLoaded() {
        if (! extension_loaded ( 'mqseries' )) {
            if (! dl ( 'mqseries.so' )) {
                throw new Exception ( Constants::MQ_EXTENTION_NOT_LOAD );
            }
        } else {
            $this->log->info ("\nMQ extension initialized.\n");
        }
    }

    /**
     * Gets connection name and port formatted.
     *
     * @return string
     */
    private function getConnectionNameAndPort() {
        $str = $this->connectionName . "(" . $this->port . ")";
        $this->log->info ("\ngetConnectionNameAndPort()" . $str . "\n");
        return $str;
    }
}

?>
