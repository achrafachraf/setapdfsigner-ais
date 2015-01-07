<?php
/**
 * This demo will sign a PDF document with a hidden OnDemand
 * signature field over All-in Signing Service from Swisscom.
 */

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);

// load and register the autoload function
require_once('../library/SetaPDF/Autoload.php');
require_once("../SetaPDF-ais.php");

// Configure the temporary writer
SetaPDF_Core_Writer_TempFile::setTempDir(sys_get_temp_dir());

// All-in customer settings
$ais_customer = 'IAM-Test:OnDemand-Advanced';

// Options
$filename_in     = 'sample.pdf';
$filename_out    = 'sample signed (OnDemand).pdf';
$signer_mail     = 'hans.muster@acme.ch';
$signer_dn       = 'cn=' . $signer_mail . ',c=ch';
$signer_location = 'Zürich';
$signer_reason   = 'I agree to the terms and condidtions in this document';

// Optional step up
$approval_no     = '+41791234567';      // Set to empty for no step up authentication
$approval_lang   = 'en';
$approval_msg    = 'Sign ' . $filename_in . ' as ' . $signer_mail . '?';
$approval_msg   .= ' (#TRANSID#)';      // Add the unique transaction ID placeholder at the end

// Create a writer and load the file
$writer = new SetaPDF_Core_Writer_File($filename_out);
$document = SetaPDF_Core_Document::loadByFilename($filename_in, $writer);

// Prepare the invisible signature
$signer = new SetaPDF_Signer($document);
$signer->setLocation($signer_location);
$signer->setReason($signer_reason);
$signer->setContactInfo($signer_mail);

// Reserve more space than default
$signer->setSignatureContentLength(32000);
$signer->setAllowSignatureContentLengthChange(false);

// Sign the document with the use of the module
$module = new SetaPDF_Signer_Signature_Module_AIS();
$module->setCustomerID($ais_customer);
$module->setSSLOptions(dirname(__FILE__).'/../mycertandkey.crt', dirname(__FILE__).'/../ais-ca-ssl.crt');

// Signature type and proper OnDemand options
$module->setOnDemandOptions($signer_dn);
if (isset($approval_no) && $approval_no !== '')
    $module->setOnDemandOptions($signer_dn, $approval_no, $approval_msg, $approval_lang);

try {
    $signer->sign($module);
    echo("Signed by: " . $module->getSignerSubject() . PHP_EOL);
    echo("Mobile ID: " . $module->getSignerMIDSN() . PHP_EOL);
} catch (SetaPDF_Exception $e) {
    // SetaPDF specific error
    echo 'Error in Core: ' . $e->getMessage() . ' with code ' . $e->getCode();
} catch (Exception $e) {
    // global exception handling
}

?>