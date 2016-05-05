<?php

use Opencontent\QueryLanguage\QueryBuilder;
use Opencontent\Opendata\Api\Values\SearchResults;
use Opencontent\Opendata\Api\Values\Content;

class CsvEnvironmentSettings extends DefaultEnvironmentSettings
{
    public function filterContent( Content $content )
    {
        $this->blockBlackListedContent( $content );
        $content = $this->removeBlackListedAttributes( $content );
        $content = $this->overrideIdentifier( $content );
        return $content->jsonSerialize();
    }

    public function filterSearchResult(SearchResults $searchResults, \ArrayObject $query, QueryBuilder $builder)
    {
        return $searchResults;
    }
}