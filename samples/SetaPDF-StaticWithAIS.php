<?php
/**
 * This demo will sign a PDF document with a hidden signature field
 * over All-in Signing Service from Swisscom.
 */

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);

// load and register the autoload function
require_once('../library/SetaPDF/Autoload.php');
require_once("../SetaPDF-ais.php");

// Configure the temporary writer
SetaPDF_Core_Writer_TempFile::setTempDir(sys_get_temp_dir());

// All-in customer settings
$ais_customer = 'IAM-Test:kp1-iam-signer';

// Options
$filename_in     = 'sample.pdf';
$filename_out    = 'sample signed (Static).pdf';

// Create a writer and load the file
$writer = new SetaPDF_Core_Writer_File($filename_out);
$document = SetaPDF_Core_Document::loadByFilename($filename_in, $writer);

// Prepare the invisible signature
$signer = new SetaPDF_Signer($document);

// Reserve more space than default
$signer->setSignatureContentLength(32000);
$signer->setAllowSignatureContentLengthChange(false);

// Sign the document with the use of the module
$module = new SetaPDF_Signer_Signature_Module_AIS();
$module->setCustomerID($ais_customer);
$module->setSSLOptions(dirname(__FILE__).'/../mycertandkey.crt', dirname(__FILE__).'/../ais-ca-ssl.crt');

try {
    $signer->sign($module);
    echo("Signed by: " . $module->getSignerSubject() . PHP_EOL);
} catch (SetaPDF_Exception $e) {
    // SetaPDF specific error
    echo 'Error in Core: ' . $e->getMessage() . ' with code ' . $e->getCode();
} catch (Exception $e) {
    // global exception handling
}

?>