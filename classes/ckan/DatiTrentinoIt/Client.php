<?php

namespace Opencontent\Ckan\DatiTrentinoIt;

use Exception;

class Client implements \OcOpenDataClientInterface
{
    /**
     * Client's API key. Required for any PUT or POST methods.
     *
     * @link    http://knowledgeforge.net/ckan/doc/ckan/api.html#ckan-api-keys
     */
    protected $apiKey;

    /**
     * Version of the CKAN API we're using.
     *
     * @var        string
     * @link    http://knowledgeforge.net/ckan/doc/ckan/api.html#api-versions
     */
    protected $apiVersion = '3';

    /**
     * URI to the CKAN web service.
     *
     * @var        string
     */
    protected $baseUrl = 'http://ckan.net/api/%d/';

    /**
     * Internal cURL object.
     */
    protected $ch;

    /**
     * cURL headers.
     */
    protected $chHeaders;

    protected $userAgent = 'Ckan_client-PHP/%s';

    protected $version = '0.1.0';

    /**
     * Standard HTTP status codes.
     *
     * @var        array
     */
    protected $httpStatusCodes = array(
        '200' => 'OK',
        '201' => 'CREATED',
        '301' => 'Moved Permanently',
        '400' => 'Bad Request',
        '403' => 'Not Authorized',
        '404' => 'Not Found',
        '409' => 'Conflict (e.g. name already exists)',
        '500' => 'Service Error'
    );

    public function __construct( $apiKey, $baseUrl )
    {
        $this->baseUrl = $baseUrl;
        $this->setApiKey( $apiKey );
    }

    public function setApiKey( $apiKey )
    {
        $this->apiKey = $apiKey;
    }

    protected function setBaseUrl()
    {
        // Append the CKAN API version to the base URI.
        $this->baseUrl = sprintf( $this->baseUrl, $this->apiVersion );
    }

    protected function setUserAgent()
    {
        if ( '80' === @$_SERVER['SERVER_PORT'] )
        {
            $server_name = 'http://' . $_SERVER['SERVER_NAME'];
        }
        else
        {
            $server_name = '';
        }
        $this->userAgent = sprintf( $this->userAgent, $this->version ) .
                           ' (' . $server_name . $_SERVER['PHP_SELF'] . ')';
    }

    protected function setHeaders()
    {
        $date = new \DateTime( null, new \DateTimeZone( 'UTC' ) );
        $this->chHeaders = array(
            'Date: ' . $date->format( 'D, d M Y H:i:s' ) . ' GMT', // RFC 1123
            'Accept: application/json;q=1.0, application/xml;q=0.5, */*;q=0.0',
            'Accept-Charset: utf-8',
            'Accept-Encoding: gzip'
        );
    }

    public function makeRequest( $method, $url, $data = null )
    {
        // Set base URI and Ckan_client user agent string.
        $this->setBaseUrl();
        $this->setUserAgent();

        $this->ch = curl_init();
        // Follow any Location: headers that the server sends.
        curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, true );
        // However, don't follow more than five Location: headers.
        curl_setopt( $this->ch, CURLOPT_MAXREDIRS, 5 );
        // Automatically set the Referer: field in requests
        // following a Location: redirect.
        curl_setopt( $this->ch, CURLOPT_AUTOREFERER, true );
        // Return the transfer as a string instead of dumping to screen.
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
        // If it takes more than 45 seconds, fail
        curl_setopt( $this->ch, CURLOPT_TIMEOUT, 45 );
        // We don't want the header (use curl_getinfo())
        curl_setopt( $this->ch, CURLOPT_HEADER, false );
        // Set user agent to Ckan_client
        curl_setopt( $this->ch, CURLOPT_USERAGENT, $this->userAgent );
        // Track the handle's request string
        curl_setopt( $this->ch, CURLINFO_HEADER_OUT, true );
        // Attempt to retrieve the modification date of the remote document.
        curl_setopt( $this->ch, CURLOPT_FILETIME, true );
        // Initialize cURL headers
        $this->setHeaders();

        // Set cURL method.
        curl_setopt( $this->ch, CURLOPT_CUSTOMREQUEST, strtoupper( $method ) );

        // Set cURL URI.
        curl_setopt( $this->ch, CURLOPT_URL, $this->baseUrl . $url );

        // If POST or PUT, add Authorization: header and request body
        if ( $method === 'POST' || $method === 'PUT' )
        {
            // We needs a key and some data, yo!
            if ( !( $this->apiKey && $data ) )
            {
                // throw exception
                throw new Exception( 'Missing either an API key or POST data.' );
            }
            else
            {
                // Add Authorization: header.
                $this->chHeaders[] = 'Authorization: ' . $this->apiKey;
                // Add data to request body.
                curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $data );
            }
        }
        else
        {
            // Since we can't use HTTPS,
            // if it's in there, remove Authorization: header
            $key = array_search(
                'Authorization: ' . $this->apiKey,
                $this->chHeaders
            );
            if ( $key !== false )
            {
                unset( $this->chHeaders[$key] );
            }
            curl_setopt( $this->ch, CURLOPT_POSTFIELDS, null );
        }

        // Set headers.
        curl_setopt( $this->ch, CURLOPT_HTTPHEADER, $this->chHeaders );

        // Execute request and get response headers.
        $response = curl_exec( $this->ch );
        $info = curl_getinfo( $this->ch );

        curl_close( $this->ch );
        unset( $this->ch );

        // Check HTTP response code
        if ( ( $info['http_code'] !== 200 && $info['http_code'] !== 201 ) )
        {
            $response = $this->parseResponse( $response, 'json' );
            $errorList = array(
                $method . ' ' . $this->baseUrl . $url,
                $info['http_code'] . ': ' . $this->httpStatusCodes[$info['http_code']]
            );
            if ( isset( $response['error'] ) )
            {
                foreach( $response['error'] as $key => $error )
                {
                    if ( $key != 'data' )
                    {
                        if ( is_array( $error ) )
                        {
                            $errorList[] = $key . ': ' . implode( ' ', $error );
                        }
                        else
                        {
                            $errorList[] = $key . ': ' . $error;
                        }
                    }
                }
            }
            throw new Exception( implode( ' ', $errorList ) );
        }

        // Determine how to parse
        if ( isset( $info['content_type'] ) && $info['content_type'] )
        {
            $contentType = str_replace( 'application/', '',
                substr( $info['content_type'], 0, strpos( $info['content_type'], ';' ) )
            );
        }
        else
        {
            $contentType = 'json';
        }
        return $this->parseResponse( $response, $contentType );
    }

    protected function parseResponse( $data = false, $format = false )
    {
        if ( $data )
        {
            if ( 'json' === $format )
            {
                $data = json_decode( $data, true );
                if ( isset( $data['result'] ) )
                {
                    return $data['result'];
                }

                return $data;
            }
            else
            {
                throw new Exception( 'Unable to parse this data format.' );
            }
        }

        return false;
    }

    /**
     * @param Organization $organization
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function pushOrganization( $organization, $forceUpdate = false )
    {
        $result = null;
        try
        {
            $result = $this->getOrganization( $organization->name );
        }
        catch ( Exception $e )
        {

        }

        if ( $result )
        {
            $organization->id = $result->id;

            $data = $this->makeRequest(
                'POST',
                'action/organization_update?id=' . $result->id,
                json_encode( $organization )
            );
        }
        else
        {
            $data = $this->makeRequest(
                'POST',
                'action/organization_create',
                json_encode( $organization )
            );
        }
        return Organization::fromArray( $data );
    }

    /**
     * @param $organizationId
     *
     * @return mixed
     * @throws Exception
     */
    public function getOrganization( $organizationId )
    {
        $data = $this->makeRequest( 'GET', 'action/organization_show?id=' . $organizationId );
        return Organization::fromArray( $data );
    }

    /**
     * @param Dataset $dataset
     *
     * @return mixed
     * @throws Exception
     */
    public function pushDataset( $dataset )
    {
//        $resources = $dataset->resources;
//        $dataset->resources = array();
        if ( $dataset->getData('id') !== null )
        {
            $data = $this->makeRequest(
                'POST',
                'action/package_update?id=' . $dataset->getData('id'),
                json_encode( $dataset )
            );
        }
        else
        {
            $data = $this->makeRequest(
                'POST',
                'action/package_create',
                json_encode( $dataset )
            );
        }
        $dataset = Dataset::fromArray( $data );
//        /** @var \Opencontent\Ckan\DatiTrentinoIt\Resource $resource */
//        foreach( $resources as $resource )
//        {
//            $resource->package_id = $dataset->getData( 'id' );
//var_dump(json_encode($resource));
//            $this->makeRequest(
//                'POST',
//                'action/resource_create',
//                json_encode( $resource )
//            );
//            $dataset->resources[] = $resource;
//        }
        return $dataset;
    }

    /**
     * @param $datasetId
     *
     * @return mixed
     * @throws Exception
     */
    public function getDataset( $datasetId )
    {
        $data = $this->makeRequest( 'GET', 'action/package_show?id=' . $datasetId );
        return Dataset::fromArray( $data );
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLicenseList()
    {
        throw new Exception( 'Not implemented' );
    }
}