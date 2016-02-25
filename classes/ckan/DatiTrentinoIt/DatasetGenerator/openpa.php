<?php

namespace Opencontent\Ckan\DatiTrentinoIt\DatasetGenerator;

use OcOpendataDatasetGeneratorInterface;
use eZContentObjectTreeNode;
use eZContentFunctions;
use OCOpenDataTools;
use eZContentObject;
use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\EnvironmentLoader;
use eZINI;
use eZUser;
use Exception;
use eZContentClass;

class OpenPA implements OcOpendataDatasetGeneratorInterface
{

    public static $remoteIds = array(
        'area' => 'opendata_area',
        'container' => 'opendata_datasetcontainer'
    );

    public function createFromClassIdentifier( $classIdentifier, $dryRun = null )
    {
        $tools = new OCOpenDataTools();

        $pagedata = new \OpenPAPageData();
        $contacts = $pagedata->getContactsData();

        $siteUrl = rtrim( eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ), '/' );
        $siteName = eZINI::instance()->variable( 'SiteSettings', 'SiteName' );

        $exists = eZContentObjectTreeNode::fetchByRemoteID( $this->generateNodeRemoteId( $classIdentifier ) );
//        if ( $exists instanceof eZContentObjectTreeNode ){
//            throw new \Exception( "Dataset autogenerated for $classIdentifier already exists" );
//        }

        $containerRemoteId = self::$remoteIds['container'];
        $container = eZContentObject::fetchByRemoteID( $containerRemoteId );
        if ( !$container instanceof eZContentObject ){
            throw new \Exception( "Dataset container (remote $containerRemoteId) not found" );
        }

        $class = eZContentClass::fetchByIdentifier( $classIdentifier );
        if (!$class instanceof eZContentClass){
            throw new \Exception( "Class $classIdentifier not found" );
        }


        $attributeList = array();

        $contentSearch = new ContentSearch();
        $contentEnvironment = EnvironmentLoader::loadPreset( 'content' );
        $geoEnvironment = EnvironmentLoader::loadPreset( 'geo' );

        $query = urlencode("classes '$classIdentifier'");

        $hasResource = false;
        $hasGeoResource = false;

        $contentSearch->setEnvironment($contentEnvironment);
        if ( $this->anonymousSearch( $contentSearch, $query ) ) {
            $resourceFieldPrefix = 'resource_1_';
            $attributeList[$resourceFieldPrefix.'api'] = "http://$siteUrl/api/opendata/v2/content/search/$query";
            $attributeList[$resourceFieldPrefix.'name'] = 'Contenuti di tipo ' . $class->attribute('name') . ' in formato JSON';
            $attributeList[$resourceFieldPrefix.'description'] = $class->attribute('description');
            $attributeList[$resourceFieldPrefix.'format'] = 'JSON';
            $attributeList[$resourceFieldPrefix.'charset'] = 'UTF-8';

            $resourceFieldPrefix = 'resource_2_';
            $attributeList[$resourceFieldPrefix.'api'] = "http://$siteUrl/exportas/custom/csv_search/$query";;
            $attributeList[$resourceFieldPrefix.'name'] = 'Contenuti di tipo ' . $class->attribute('name') . ' in formato CSV';
            $attributeList[$resourceFieldPrefix.'description'] = $class->attribute('description');
            $attributeList[$resourceFieldPrefix.'format'] = 'CSV';
            $attributeList[$resourceFieldPrefix.'charset'] = 'UTF-8';
            $hasResource = true;
        }

        $resourceFieldPrefix = 'resource_3_';
        $contentSearch->setEnvironment($geoEnvironment);
        if ( $this->anonymousSearch( $contentSearch, "classes '$classIdentifier'" ) ) {
            $attributeList[$resourceFieldPrefix.'api'] = "http://$siteUrl/api/opendata/v2/content/geo/$query";
            $attributeList[$resourceFieldPrefix.'name'] = 'Contenuti di tipo ' . $class->attribute('name') . ' in formato GeoJSON';
            $attributeList[$resourceFieldPrefix.'description'] = $class->attribute('description');
            $attributeList[$resourceFieldPrefix.'format'] = 'GeoJSON';
            $attributeList[$resourceFieldPrefix.'charset'] = 'UTF-8';
            $hasGeoResource = true;
        }

        if ( !$hasResource ){
            throw new \Exception( "Nessuna risorsa trovata per $classIdentifier" );
        }

        if ( $hasGeoResource )
            $resourceFieldPrefix = 'resource_4_';

        $attributeList[$resourceFieldPrefix.'api'] = "http://$siteUrl/api/opendata/v2/classes/$classIdentifier";
        $attributeList[$resourceFieldPrefix.'name'] = 'Descrizione dei campi di contenuri di tipo ' . $class->attribute('name') . ' in formato JSON';
        $attributeList[$resourceFieldPrefix.'format'] = 'JSON';
        $attributeList[$resourceFieldPrefix.'charset'] = 'UTF-8';

        $attributeList['title'] = 'Contenuti di tipo ' . $class->attribute('name') . ' del ' . $siteName;
        $attributeList['author'] = $siteName . '|' . $contacts['email'];
        $attributeList['maintainer'] = $siteName . '|' . $contacts['email'];;
        $attributeList['url_website'] = null;
        $attributeList['notes'] = 'Endpoint JSON e CSV dei contenuti di tipo ' . $class->attribute('name') . ' del ' . $siteName;;
        $attributeList['tech_documentation'] = null;
        $linkHelp = "http://$siteUrl/opendata/help/classes/$classIdentifier";
        $attributeList['fields_description_text'] = "[Link]($linkHelp) alla pagina di descrizione dei campi.";
        $attributeList['geo'] = str_replace( 'Comune di', '', $siteName );
        $attributeList['frequency'] = 'Continuo';
        $attributeList['license_id'] = 'CC-BY';
        $attributeList['versione'] = '1.0';
        $attributeList['tags'] = $classIdentifier . ', json, csv';
        if ( $hasGeoResource )
            $attributeList['tags'] .= ', geojson';

        $params                     = array();
        $params['class_identifier'] = $tools->getIni()->variable( 'GeneralSettings', 'DatasetClassIdentifier' );
        $params['parent_node_id']   = $container->attribute('main_node_id');
        $params['attributes']       = $attributeList;

        if ( $dryRun )
            return true;

        if ( $exists ){
            $object = $exists->object();
            eZContentFunctions::updateAndPublishObject( $object, $params );
        }else{
            $object = eZContentFunctions::createAndPublishObject( $params );
        }
        if ( $object instanceof eZContentObject){
            $mainNode = $object->attribute( 'main_node' );
            if ( $mainNode instanceof eZContentObjectTreeNode) {
                $mainNodeUrlAlias = $mainNode->attribute('url_alias');
                /** @var \eZContentObjectAttribute[] $dataMap */
                $dataMap = $object->attribute('data_map');
                $dataMap['url_website']->fromString($siteUrl . '/' . $mainNodeUrlAlias . '|' . $object->attribute('name'));
                $dataMap['url_website']->store();
                $mainNode->setAttribute( 'remote_id', $this->generateNodeRemoteId($classIdentifier));
                $mainNode->store();
            }
        }
        return $object;
    }

    protected function generateNodeRemoteId( $classIdentifier ){
        return 'auto_dataset_' . $classIdentifier;
    }

    protected function anonymousSearch( ContentSearch $contentSearch, $query ){
        $anonymousId = eZINI::instance()->variable('UserSettings','AnonymousUserID');
        $loggedUser = eZUser::currentUser();
        $anonymousUser = \eZUser::fetch( $anonymousId );

        if ( $anonymousUser instanceof eZUser )
        {
            eZUser::setCurrentlyLoggedInUser( $anonymousUser, $anonymousUser->attribute( 'contentobject_id' ), 1 );
        }
        try
        {
            $result = $contentSearch->search( $query );
            $count = $result->totalCount;
        }
        catch ( Exception $e  )
        {
            eZUser::setCurrentlyLoggedInUser( $loggedUser, $loggedUser->attribute( 'contentobject_id' ), 1 );
            $count = 0;
        }

        eZUser::setCurrentlyLoggedInUser( $loggedUser, $loggedUser->attribute( 'contentobject_id' ), 1 );
        return $count > 0;

    }
}