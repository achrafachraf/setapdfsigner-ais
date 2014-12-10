<?php
/**
 * @version        1.0.0
 * @package        SetaPDF_Signer_Signature_Module_AIS
 * @copyright      Copyright (C) 2014. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.md
 * @author         Swisscom (Schweiz) AG
 * Requirements    AllinSigningService (ais.php 1.0.0)
 *                 SetaPDF-Signer (Version 2.1.0.x)
 *
 * Open Issues     Support for Timestamp only signatures
 */

require_once dirname(__FILE__) . '/ais.php';

class SetaPDF_Signer_Signature_Module_AIS implements SetaPDF_Signer_Signature_Module_ModuleInterface {
    private $customerID;                    // CustomerID provided by Swisscom
    private $certandkey;                    // Certificate/key that is allowed to access the service
    private $ca_ssl;                        // Location of Certificate Authority file which should be used to authenticate the identity of the remote peer
    private $ais_options;                   // Additional SOAP client options
    private $digestAlgo;                    // Digest algorithm
    private $digestMethod;                  // Digest method URL
    private $DN;                            // OnDemand options
    private $msisdn;
    private $msg;
    private $lang;

    public $signerSubject;                  // Signer: Subject
    public $signerMIDSN;                    // Signer: SerialNumber of Distinguished Name, if present

    /**
     * AIS Signature Module class
     * #params     string    (optional) Customer ID provided by Swisscom
     * #params     string    (optional) Certificate/key that is allowed to access the service
     * #params     string    (optional) Location of Certificate Authority file which should be used to authenticate the identity of the remote peer
     * #params     array     (optional) Additional SOAP client options
     * @return     null
     */
    public function __construct($customerID='', $cert='', $cafile='', $myOpts = null) {
        $this->setCustomerID($customerID);
        $this->setSSLOptions($cert, $cafile);
        $this->ais_options = $myOpts;
        $this->setDigestAlgo('sha256');
        $this->DN = '';
        $this->msisdn = '';
        $this->msg = '';
        $this->lang = '';
        $this->signerSubject = '';
        $this->signerMIDSN = '';
    }

    /**
     * setCustomerID - Defines the customer identification
     * #params     string    Customer ID provided by Swisscom
     */
    public function setCustomerID($customerID) {
        $this->customerID = (string)$customerID;
    }

    /**
     * setSSLOptions - Defines the SSL identification options
     * #params     string    Certificate/key that is allowed to access the service
     * #params     string    Location of CA file which should be used to authenticate the identity of the remote peer
     */
    public function setSSLOptions($certandkey, $ca_ssl) {
        $this->certandkey = (string)$certandkey;
        $this->ca_ssl = (string)$ca_ssl;
    }

    /**
     * setOnDemandOptions - Defines the OnDemand and optional step up values
     * #params     string    Distiuished Name (DN)
     * #params     string    (optional) Mobile ID Number for step up verification
     * #params     string    (optional) Mobile ID Message
     * #params     string    (optional) Mobile ID Language
     */
    public function setOnDemandOptions($DN, $msisdn='', $msg='', $lang='') {
        $this->DN = (string)$DN;
        $this->msisdn = (string)$msisdn;
        $this->msg = (string)$msg;
        $this->lang = (string)$lang;
    }
    
    /**
     * setDigestAlgo - Defines the dighest method to be used (Default is SHA-256)
     * #params     string    Type ('SHA-256', 'SHA-384', 'SHA-512')
     */
    public function setDigestAlgo($algo) {
        $algo = strtoupper((string)$algo);
        switch ($algo) {
            case 'SHA-384':
                $this->digestAlgo = 'sha384';
                $this->digestMethod = 'http://www.w3.org/2001/04/xmldsig-more#sha384';
                break;
            case 'SHA-512':
                $this->digestAlgo = 'sha512';
                $this->digestMethod = 'http://www.w3.org/2001/04/xmlenc#sha512';
                break;
            case 'SHA-256':
            default:
                $this->digestAlgo = 'sha256';
                $this->digestMethod = 'http://www.w3.org/2001/04/xmlenc#sha256';
        }
    }

    /**
     * createSignature - Creates a PADES signature over AIS for the given file including a Timestamp
     * #params     string    File to be signed
     * @return     string    CMS signature
     */
    public function createSignature($tmpPath) {
        $this->signerSubject = '';
        $this->signerMIDSN = '';
        $digestValue = hash_file($this->digestAlgo, $tmpPath, true);

        $ais = new AllinSigningService($this->customerID, $this->certandkey, $this->ca_ssl, $this->ais_options);
        $ais->addRevocationInformation('PADES');
        $ais->addTimeStamp(true);
    
        $ok = $ais->sign($digestValue, $this->digestMethod, $this->DN, $this->msisdn, $this->msg, $this->lang);
        if (! $ok) {
            $error = 'Module_AIS#' . (string)$ais->resultmajor . '::' . (string)$ais->resultminor;
            $errorMobileID = preg_replace('/^mss:_/', '', $ais->resultmessage);
            switch ($error) {
                case 'Module_AIS#HTTP::Could not connect to host':
                    throw new SetaPDF_Signer_Exception($error, 1);
                case 'Module_AIS#http://ais.swisscom.ch/1.0/resultmajor/SubsystemError::http://ais.swisscom.ch/1.0/resultminor/subsystem/MobileID/service':
                    throw new SetaPDF_Signer_Exception('ModuleAIS#MobileID', (integer)$errorMobileID);
                default:
                    throw new SetaPDF_Signer_Exception($error, -1);
            }
        }
        $this->signerSubject = $ais->sig_certSubject;
        $this->signerMIDSN = $ais->sig_certMIDSN;
        return(base64_decode($ais->getLastSignature()));
    }
    
    /**
     * getSignerSubject - Returns signature subject for the last request
     * @return     string
     */
    public function getSignerSubject() {
        return($this->signerSubject);
    }

    /**
     * getSignerMIDSN - Returns Mobile ID Serialnumber of the DN related to last signature
     * @return     string
     */
    public function getSignerMIDSN() {
        return($this->signerMIDSN);
    }

}

?>