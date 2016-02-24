<?php

$Module = array( 'name' => 'OpenData' );

$ViewList = array();
$ViewList['push'] = array(
    'functions' => array( 'push' ),
    'script' => 'push.php',
    'params' => array( 'ObjectID' ),
    'unordered_params' => array()
);
$ViewList['view'] = array(
    'functions' => array( 'view' ),
    'script' => 'view.php',
    'params' => array( 'ObjectID' ),
    'unordered_params' => array()
);
$ViewList['import'] = array(
    'functions' => array( 'import' ),
    'script' => 'import.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['api'] = array(
    'functions' => array( 'api' ),
    'script' => 'api.php',
    'params' => array( 'Environment', 'Action', 'Param' ),
    'unordered_params' => array()
);
$ViewList['console'] = array(
    'functions' => array( 'console' ),
    'script' => 'console.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['analyzer'] = array(
    'functions' => array( 'analyzer' ),
    'script' => 'analyzer.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['datatable'] = array(
    'functions' => array( 'datatable' ),
    'script' => 'datatable.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['help'] = array(
    'functions' => array( 'console' ),
    'script' => 'help.php',
    'params' => array( 'Section', 'Identifier' ),
    'unordered_params' => array()
);

$FunctionList = array();
$FunctionList['api'] = array();
$FunctionList['console'] = array();
$FunctionList['analyzer'] = array();
$FunctionList['datatable'] = array();
$FunctionList['push'] = array();
$FunctionList['view'] = array();
$FunctionList['import'] = array();

$presetList = array();
foreach( \Opencontent\Opendata\Api\EnvironmentLoader::getAvailablePresetIdentifiers() as $preset )
{
    $presetList[$preset] = array( 'Name' => $preset, 'value' => $preset );
}
$FunctionList['environment'] = array(
    'PresetList' => array(
        'name' => 'PresetList',
        'values' => $presetList
    )
);

