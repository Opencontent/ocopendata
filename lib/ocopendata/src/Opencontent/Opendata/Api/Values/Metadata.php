<?php

namespace Opencontent\Opendata\Api\Values;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;
use eZContentObject;
use eZContentObjectAttribute;
use eZSection;
use eZContentLanguage;
use eZContentObjectTreeNode;

class Metadata
{
    public $id;

    public $remoteId;

    public $classIdentifier;

    public $classId;

    public $languages;

    public $name;

    public $ownerId;

    public $mainNodeId;

    public $parentNodes;

    public $assignedNodes;

    public $sectionIdentifier;

    public $sectionId;

    public $stateIdentifiers;

    public $stateIds;

    public $published;

    public $modified;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( property_exists( $this, $property ) )
                $this->$property = $value;
            else
                throw new OutOfRangeException( $property );
        }
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }

    public function jsonSerialize()
    {
        return $this;
    }

    /**
     * @param eZContentObject $contentObject
     *
     * @return Metadata
     */
    public static function createFromEzContentObject( eZContentObject $contentObject )
    {
        $languages = eZContentLanguage::fetchLocaleList();
        /** @var eZContentLanguage[] $availableLanguages */
        $availableLanguages = array_keys( $contentObject->allLanguages() );
        $metadata = new Metadata();
        $metadata->id = (int)$contentObject->attribute( 'id' );
        $names = array();
        foreach ( $languages as $language )
        {
            if ( in_array( $language, $availableLanguages ) )
            {
                $names[$language] = $contentObject->name( false, $language );
            }
        }
        $metadata->name = $names;
        $metadata->remoteId = $contentObject->attribute( 'remote_id' );
        $metadata->ownerId = (int)$contentObject->attribute( 'owner_id' );
        $metadata->classIdentifier = $contentObject->attribute( 'class_identifier' );
        $metadata->classId = $contentObject->attribute( 'contentclass_id' );
        $metadata->mainNodeId = $contentObject->attribute( 'main_node_id' );
        $metadata->parentNodes = array();
        foreach( $contentObject->attribute( 'parent_nodes' ) as $node )
        {
            $parentNode = eZContentObjectTreeNode::fetch( $node, false, false );
            $metadata->parentNodes[] = array(
                'id' => (int)$parentNode['node_id'],
                'depth' => (int)$parentNode['depth'],
                'path_string' => $parentNode['path_string']
            );
        }
        $metadata->assignedNodes = array();
        /** @var \eZContentObjectTreeNode $node */
        foreach( $contentObject->attribute( 'assigned_nodes' ) as $node )
        {
            $metadata->assignedNodes[] = array(
                'id' => (int)$node->attribute( 'node_id' ),
                'depth' => (int)$node->attribute( 'depth' ),
                'path_string' => $node->attribute( 'path_string' )
            );
        }
        $metadata->published = date( 'c', $contentObject->attribute( 'published' ) );
        $metadata->modified = date( 'c', $contentObject->attribute( 'modified' ) );
        $section = eZSection::fetch( $contentObject->attribute( 'section_id' ) );
        if ( $section instanceof eZSection )
        {
            $metadata->sectionIdentifier = $section->attribute( 'identifier' );
            $metadata->sectionId = $section->attribute( 'id' );
        }
        $metadata->stateIdentifiers = array();
        foreach ( $contentObject->stateIdentifierArray() as $identifier )
        {
            $metadata->stateIdentifiers[] = str_replace( '/', '.', $identifier );
        }
        $metadata->stateIds = $contentObject->stateIDArray();
        $metadata->languages = $availableLanguages;

        return $metadata;
    }

}