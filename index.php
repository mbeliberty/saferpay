<?php

namespace Payment\Saferpay;

require "../../../../autoload.php";

use Payment\Saferpay\Saferpay;
use Payment\Saferpay\Http\Client\GuzzleClient;

session_start();

// create a new saferpay instance (implement as service)
$saferpay = new Saferpay();

// get all config data from json
$arrConfig = json_decode(file_get_contents('config.json'), true);

// update config
$saferpay->getConfig()->setInitUrl($arrConfig['urls']['init']);
$saferpay->getConfig()->setConfirmUrl($arrConfig['urls']['confirm']);
$saferpay->getConfig()->setCompleteUrl($arrConfig['urls']['complete']);

// set validation config
$saferpay->getConfig()->setInitValidationsConfig($saferpay->getKeyValuePrototype($arrConfig['validators']['init']));
$saferpay->getConfig()->setConfirmValidationsConfig($saferpay->getKeyValuePrototype($arrConfig['validators']['confirm']));
$saferpay->getConfig()->setCompleteValidationsConfig($saferpay->getKeyValuePrototype($arrConfig['validators']['complete']));

var_dump($saferpay->getKeyValuePrototype()); die();

// set default config
$saferpay->getConfig()->setInitDefaultsConfig($saferpay->getKeyValuePrototype($arrConfig['defaults']['init']));
$saferpay->getConfig()->setConfirmDefaultsConfig($saferpay->getKeyValuePrototype($arrConfig['defaults']['confirm']));
$saferpay->getConfig()->setCompleteDefaultsConfig($saferpay->getKeyValuePrototype($arrConfig['defaults']['complete']));

// get session data if exists
$saferpayData = isset($_SESSION) && is_array($_SESSION) && array_key_exists('saferpay.data', $_SESSION) ? $_SESSION['saferpay.data'] : null;

// set data
$saferpay->setData($saferpayData);

// set guzzle as http client
$saferpay->setHttpClient(new GuzzleClient());

if(getParam('status') == 'success')
{
    if($saferpay->confirmPayment(getParam('DATA'), getParam('SIGNATURE')) != '')
    {
        $lastresponse = $saferpay->completePayment();

        if($lastresponse != '')
        {
            unset($_SESSION['saferpay.data']);
        }
    }
}
else
{
    $url = $saferpay->initPayment($saferpay->getKeyValuePrototype(array(
        'AMOUNT' => 10250,
        'DESCRIPTION' => sprintf('Bestellnummer: %s', '000001'),
        'ORDERID' => '000001',
        'SUCCESSLINK' => requestUrl() . '?status=success',
        'FAILLINK' => requestUrl() . '?status=fail',
        'BACKLINK' => requestUrl(),
        'GENDER' => 'm',
        'FIRSTNAME' => 'Hans',
        'LASTNAME' => 'Muster',
        'STREET' => 'Musterstrasse 300',
        'ZIP' => '0000',
        'CITY' => 'Musterort',
        'COUNTRY' => 'CH',
        'EMAIL' => 'test@test.ch'
    )));

    // assign the data to the session
    $_SESSION['saferpay.data'] = $saferpay->getData();

    if($url != '')
    {
        // redirect to saferpay
        header("Location: {$url}", 302);
    }
}

// show saferpay object
printData($saferpay);

function requestUrl()
{
    $protocol = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/')));
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getParam($key, $default = null)
{
    return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function printData($mixData, $boolDie = false)
{
    echo '<pre>';
    print_r($mixData);
    echo '</pre>';
    if($boolDie){ die(); }
}