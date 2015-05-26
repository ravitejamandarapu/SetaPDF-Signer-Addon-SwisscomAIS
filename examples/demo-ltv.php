<?php
/* This demo shows you how create a signature through the Swisscom
 * All-in Signing Service and how to add the revoke information the
 * PDF documents Document Security Store (DSS).
 *
 * More information about AIS are available here:
 * https://www.swisscom.ch/en/business/enterprise/offer/security/identity-access-security/signing-service.html
 */
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// load and register the autoload function
require_once('../vendor/autoload.php');

if (file_exists('credentials.php')) {
    // The vars are defined in this file for privacy reason.
    require('credentials.php');
} else {
    // path to your certificate and private key
    $cert = realpath('mycertandkey.crt');
    $passphrase = 'Passphrase for the private key in $cert';
    // your <customer name>:<key entity>
    $customerId = "";
}

// options for the SoapClient instance
$clientOptions = array(
    'stream_context' => stream_context_create(array(
        'ssl' => array(
            'verify_peer' => true,
            'cafile' => __DIR__ . '/ais-ca-ssl.crt',
            'peer_name' => 'ais.swisscom.com'
        )
    )),
    'local_cert' => $cert,
    'passphrase' => $passphrase
);

// the signature field name
$signatureFieldName = 'Signature';

// let's prepare the temporary file writer
SetaPDF_Core_Writer_TempFile::setTempDir(realpath('_tmp/'));

// let's create final and temporary writer instances
$writer = new SetaPDF_Core_Writer_Http('Swisscom-Ltv.pdf');
$tempWriter = new SetaPDF_Core_Writer_TempFile();
// let's get the document
$document = SetaPDF_Core_Document::loadByFilename('files/tektown/Laboratory-Report.pdf', $tempWriter);

// now let's create a signer instance
$signer = new SetaPDF_Signer($document);
$signer->setAllowSignatureContentLengthChange(false);
$signer->setSignatureContentLength(32000);

// set the signature field name
$signer->setSignatureFieldName($signatureFieldName);

// set some signature properies
$signer->setLocation($_SERVER['SERVER_NAME']);
$signer->setContactInfo('+01 2345 67890123');
$signer->setReason('testing...');

// create an Swisscom AIS module instance
$module = new SetaPDF_Signer_SwisscomAIS_Module($customerId, $clientOptions);
// let's add PADES revoke information to the resulting signatures
$module->setAddRevokeInformation('PADES');
// additionally the signature should include a qualified timestamp
$module->setAddTimestamp(true);

// sign the document with the use of the module
$signer->sign($module);

// get a document instance of the temporary result
$document = SetaPDF_Core_Document::loadByFilename($tempWriter->getPath(), $writer);

// update the DSS with the revoke information of the last response
$module->updateDss($document, $signatureFieldName);

// save and finish
$document->save()->finish();