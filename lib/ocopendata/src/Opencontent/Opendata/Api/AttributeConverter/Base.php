<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\Exception\InvalidInputException;
use Opencontent\Opendata\Api\PublicationProcess;

class Base
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $classIdentifier;


    public function __construct(
        $classIdentifier,
        $identifier
    ) {
        $this->classIdentifier = $classIdentifier;
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param eZContentObjectAttribute $attribute
     *
     * @return array|string|int|null|\JsonSerializable
     */
    public function get(eZContentObjectAttribute $attribute)
    {
        $data = array(
            'id' => intval($attribute->attribute('id')),
            'version' => intval($attribute->attribute('version')),
            'identifier' => $this->classIdentifier . '/' . $this->identifier,
            'datatype' => $attribute->attribute('data_type_string'),
            'content' => $attribute->hasContent() ? $attribute->toString() : null
        );

        return $data;
    }

    public function set($data, PublicationProcess $process)
    {
        return $data;
    }

    public static function validate($identifier, $data, eZContentClassAttribute $attribute)
    {
        if ($data !== null && !is_string($data)) {
            throw new InvalidInputException('Invalid type', $identifier, $data);
        }
    }

    /**
     * @param eZContentClassAttribute $attribute
     *
     * @return string|null
     */
    public function help(eZContentClassAttribute $attribute)
    {
        return null;
    }

    public function type(eZContentClassAttribute $attribute)
    {
        if ($attribute->attribute('is_information_collector')) {
            return array('identifier' => 'readonly');
        }

        return array('identifier' => 'string');
    }

    public static function clean()
    {

    }

    public function toCSVString($content, $params = null)
    {
        return is_string($content) ? $content : '';
    }
}