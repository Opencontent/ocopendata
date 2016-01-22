<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\Exception\InvalidInputException;


class Matrix extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        /** @var \eZMatrix $attributeContents */
        $attributeContents = $attribute->content();
        $columns = (array) $attributeContents->attribute( 'columns' );
        $rows = (array) $attributeContents->attribute( 'rows' );

        $keys = array();
        foreach( $columns['sequential'] as $column )
        {
            $keys[] = $column['identifier'];
        }
        $data = array();
        foreach( $rows['sequential'] as $row )
        {
            $data[] = array_combine( $keys, $row['columns'] );
        }
        $content['content'] = $data;
        return $content;
    }

    public function set( $data )
    {
        $rows = array();
        foreach( $data as $item )
        {
            $rows[] = implode( '|', array_values( $item ) );
        }
        return implode( '&', $rows );
    }

    public static function validate( $identifier, $data, eZContentClassAttribute $attribute )
    {
        if ( !is_array( $data ) )
        {
            throw new InvalidInputException( 'Invalid type',$identifier, $data );
        }

        $format = self::getMatrixFormat( $attribute );
        $keys = array_keys( $format );
        foreach( $data as $item )
        {
            $diff = array_diff( $keys, array_keys( $item ) );
            if ( !empty( $diff ) )
            {
                throw new InvalidInputException( 'Invalid hash', $identifier, $item );
            }
        }
    }

    public function type( eZContentClassAttribute $attribute )
    {
        $format = self::getMatrixFormat( $attribute );
        return array(
            'identifier' => 'array of objects',
            'format' => array(
                $format
            )
        );
    }

    protected static function getMatrixFormat( eZContentClassAttribute $attribute )
    {
        /** @var \eZMatrixDefinition $definition */
        $definition = $attribute->attribute('content');
        $columns = $definition->attribute( 'columns' );
        $format = array();
        foreach( $columns as $column )
        {
            $format[$column['identifier']] = "string ({$column['name']})";
        }
        return $format;
    }
}   