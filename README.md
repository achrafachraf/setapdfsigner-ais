setapdfsigner-ais
=================

Contains SetaPDF-Signer ModuleInterface to create a signature with the All-in Signing service from Swisscom.

Refer to SetaPDF-Signer documentation for additional details http://www.setasign.com/products/setapdf-signer/details/


## Dependencies

The class is using:

* SetaPDF-Signer (is subject to licensing and must be acquired separately)
* AllinSigningService (ais.php version 1.0.0 and the related WSDL)
* SoapClient class (http://www.php.net/manual/en/class.soapclient.php) in the WSDL mode
* OpenSSL package and class (http://www.php.net/manual/en/openssl.requirements.php)

## Client based certificate authentication

The file that must be specified in the initialisation refers to the local_cert and must contain both certificates, privateKey and publicKey in the same file (`cat mycert.crt mycert.key > mycertandkey.crt`).

Example of content:
````
-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----
-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----
````

## Connection options

Proxy support by passing additional SoapClient options.

````
$myoptions = array(
    'proxy_host'     => "localhost",
    'proxy_port'     => 8080,
    'proxy_login'    => "some_name",
    'proxy_password' => "some_password"
);
$module = new SetaPDF_Signer_Signature_Module_AIS($customerID, $certandkey, $ca_ssl, $myoptions);
````

Refer to the SoapClient::SoapClient options on http://www.php.net/manual/en/soapclient.soapclient.php

## Usage

````
...
// Reserve more space than default
$signer->setSignatureContentLength(32000);
$signer->setAllowSignatureContentLengthChange(false);

// Sign the document with the use of the module
$module = new SetaPDF_Signer_Signature_Module_AIS();
$module->setCustomerID($ais_customer);
$module->setSSLOptions(dirname(__FILE__).'/mycertandkey.crt', dirname(__FILE__).'/ais-ca-ssl.crt');
...
$signer->sign($module);
...
````

Samples:

* Static Signature [samples/SetaPDF-StaticWithAIS.php](samples/SetaPDF-StaticWithAIS.php)
* OnDemand Signature [samples/SetaPDF-OnDemandWithAIS.php](samples/SetaPDF-OnDemandWithAIS.php)



## Known issues

A ModuleInterface for the Timestamp Signature is not yet available.
