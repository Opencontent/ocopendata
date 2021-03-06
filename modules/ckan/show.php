<?php
/** @var eZModule $Module */
$Module = $Params['Module'];
$ObjectID = $Params['ObjectID'];
$Alias = $Params['Alias'];

$html = '<html>';
$html .= '<head>';
$html .= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">';
$html .= '</head>';
$html .= '<body>';
$html .= '<div class="container">';

try {
    if ($ObjectID == 'org') {
        $tools = new OCOpenDataTools($Alias);
        $id = $tools->getOrganizationBuilder()->getStoresOrganizationId();
        $data = $tools->getClient()->getOrganization($id);

    } else {
        $tools = new OCOpenDataTools($Alias);
        $object = eZContentObject::fetch($ObjectID);
        if (!$object instanceof eZContentObject){
            throw new Exception("Object $ObjectID not found");
        }
        $datasetId = $tools->getConverter()->getDatasetId($object);
        $data = $tools->getClient()->getDataset($datasetId);
    }

    $json = array(
        'result' => 'success',
        'data' => $data
    );

    $html .= '<pre>' . print_r($data, 1) .'</pre>';

} catch (Exception $e) {

    $json = array(
        'result' => 'error',
        'error' => $e->getMessage()
    );

    $html .= "<h1>".$e->getMessage()."</h1>";

    if ($e instanceof OCOpenDataRequestException){
        $html .= "<h3>" . $e->getResponseCode() . ' ' . $e->getResponseCodeMessage() . "</h3>";

        $html .= '<pre>' . print_r($e->getResponseError(), 1) . '</pre>';
    }

    try {
        if (is_numeric($ObjectID)) {
            $object = eZContentObject::fetch($ObjectID);
            $tools = new OCOpenDataTools($Alias);
            $data = $tools->getDatasetFromObject($object);
        } else {
            $data = $tools->organizationBuilder->build();
        }

        $html .= '<pre>' . print_r($data, 1) . '</pre>';

    } catch (Exception $error) {
        $html .= "<em>Conversione non riuscita</em> ({$error->getMessage()})";
    }

}

$html .= '</div>';
$html .= '</body>';
$html .= '</html>';


$format = 'json';
if (eZHTTPTool::instance()->hasGetVariable('format')){
    $format = eZHTTPTool::instance()->getVariable('format');
}

if ($format == 'json'){
    header('Content-Type: application/json');
    echo json_encode( $json );
}elseif ($format == 'html'){
    echo $html;
    eZDisplayDebug();
}

eZExecution::cleanExit();
