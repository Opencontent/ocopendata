<?php

use Opencontent\Opendata\Api\EnvironmentLoader;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\ClassRepository;

class OCOpenDataOperators
{
    function operatorList()
    {
        return array(
            'fetch_licenses',
            'fetch_charsets',
            'api_search',
            'api_read',
            'api_class'
        );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array(
            'fetch_licenses' => array(),
            'fetch_charsets' => array(),
            'api_search' => array(
                'query' => array( 'type' => 'string', 'required' => true, 'default' => false ),
                'environment' => array( 'type' => 'string', 'required' => false, 'default' => 'content' )
            ),
            'api_read' => array(
                'query' => array( 'type' => 'string', 'required' => true, 'default' => false ),
                'environment' => array( 'type' => 'string', 'required' => false, 'default' => 'content' )
            ),
            'api_class' => array(
                'identifier' => array( 'type' => 'string', 'required' => true, 'default' => false )
            )
        );
    }

    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {

        switch ($operatorName)
        {
            case 'api_class':
            {
                $identifier = $namedParameters['identifier'];
                $classRepository = new ClassRepository();
                try
                {
                    $data = (array)$classRepository->load( $identifier );
                }
                catch( Exception $e )
                {
                    $data = array(
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    );
                }
                $operatorValue = $data;
            } break;

            case 'api_search':
            case 'api_read':
            {
                $Environment = $namedParameters['environment'];
                $Action = $operatorName == 'api_search' ? 'search' : 'read';
                $Param = $namedParameters['query'];

                try
                {
                    $contentRepository = new ContentRepository();
                    $contentSearch = new ContentSearch();

                    $currentEnvironment = EnvironmentLoader::loadPreset( $Environment );
                    $contentRepository->setEnvironment( $currentEnvironment );
                    $contentSearch->setEnvironment( $currentEnvironment );

                    $parser = new ezpRestHttpRequestParser();
                    $request = $parser->createRequest();
                    $currentEnvironment->__set('request', $request);

                    $data = array();

                    if ( $Action == 'read' )
                    {
                        $data = (array)$contentRepository->read( $Param );
                    }
                    elseif ( $Action == 'search' )
                    {
                        $data = (array)$contentSearch->search( $Param );
                    }
                }
                catch( Exception $e )
                {
                    $data = array(
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    );
                }
                $operatorValue = $data;
            } break;

            case 'fetch_charsets':
                $returnArray = mb_list_encodings();
                $operatorValue = $returnArray;
                break;

            case 'fetch_licenses':
                $openDataTools = new OCOpenDataTools();
                $licenses = $openDataTools->getLicenseList();
                $returnArray = array();
                foreach( $licenses as $license )
                {
                    $returnArray[$license->id] = $license->title;
                }
                $operatorValue = $returnArray;
                break;
        }
    }

}
