<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentClass;
use Opencontent\Opendata\Api\Values\SearchResults;


class EnvironmentSettings
{
    protected $identifier;

    protected $defaultParentNodeId;

    protected $multimediaParentNodeId;

    protected $fileParentNodeId;

    protected $imageParentNodeId;

    protected $remoteIdPrefix;

    protected $validatorTolerance;

    protected $debug;

    protected $maxSearchLimit = 300;

    protected $defaultSearchLimit = 100;

    protected $requestBaseUri;

    protected $request;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( property_exists( $this, $property ) )
            {
                $this->$property = $value;
            }
            else
            {
                throw new OutOfRangeException( $property );
            }
        }
    }

    public function __get( $property )
    {
        if ( property_exists( $this, $property ) )
        {
            return $this->{$property};
        }
        throw new OutOfRangeException( $property );
    }

    public function __set( $property, $value )
    {
        if ( property_exists( $this, $property ) )
        {
            $this->{$property} = $value;
        }
        else
        {
            throw new OutOfRangeException( $property );
        }
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }

    /**
     * @param Content $content
     *
     * @return array or array cast object
     */
    public function filterContent( Content $content )
    {
        return $content->jsonSerialize();
    }

    /**
     * @param SearchResults $searchResults
     *
     * @return \ArrayAccess
     */
    public function filterSearchResult( SearchResults $searchResults )
    {
        if ( $searchResults->nextPageQuery != null )
        {
            $searchResults->nextPageQuery = $this->requestBaseUri . 'search/' . urlencode( $searchResults->nextPageQuery );
        }
        return $searchResults;
    }

    /**
     * @param \ArrayObject $query
     *
     * @return \ArrayObject
     */
    public function filterQuery( \ArrayObject $query )
    {
        if ( isset( $query['SearchLimit'] ) )
        {
            if ( $query['SearchLimit'] > $this->maxSearchLimit )
                $query['SearchLimit'] = $this->maxSearchLimit;
        }else
        {
            $query['SearchLimit'] = $this->defaultSearchLimit;
        }

        if ( !isset( $query['SearchOffset'] ) )
        {
            $query['SearchOffset'] = 0;
        }
        return $query;
    }

}