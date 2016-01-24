<?php

namespace Opencontent\Opendata\Api;

use eZDir;
use eZSys;
use eZClusterFileHandler;
use eZSection;
use Opencontent\Opendata\Api\Exception\NotFoundException;

class SectionRepository
{
    public function load( $identifier )
    {
        $all = $this->internalLoadSections();
        foreach( $all as $section )
        {
            if ( ( is_numeric( $identifier ) && $section['id'] == $identifier )
                 || ( $section['identifier'] == $identifier ) )
            {
                return $section;
            }
        }
        throw new NotFoundException( $identifier, 'Section' );
    }

    public function loadAll()
    {
        return $this->internalLoadSections();
    }

    protected function internalLoadSections()
    {
        return $this->getCacheManager()->processCache(
            array( __CLASS__, 'retrieveCache' ),
            array( __CLASS__, 'generateCache' ),
            null,
            null,
            'sections'
        );
    }

    protected static function getCacheManager()
    {
        $cacheFile = 'sections.cache';
        $cacheFilePath = eZDir::path(
            array( eZSys::cacheDirectory(), 'ocopendata', $cacheFile )
        );

        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public function clearCache()
    {
        $this->getCacheManager()->purge();
    }

    public static function retrieveCache( $file, $mtime, $identifier )
    {
        $content = include( $file );

        return $content;
    }

    public static function generateCache( $file, $identifier )
    {
        $sectionList = eZSection::fetchObjectList(
            eZSection::definition(),
            null,
            null,
            null,
            null,
            false
        );
        return array(
            'content' => $sectionList,
            'scope' => 'ocopendata-cache',
            'datatype' => 'php',
            'store' => true
        );
    }
}