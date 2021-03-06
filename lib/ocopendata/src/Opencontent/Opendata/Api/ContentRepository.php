<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Exception\DuplicateRemoteIdException;
use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\Exception\ForbiddenException;
use Opencontent\Opendata\Api\Values\Content;

class ContentRepository
{
    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    /**
     * @var Gateway
     */
    protected $gateway;

    public function __construct()
    {
        //        $this->gateway = new Database();      // fallback per tutti
        //        $this->gateway = new SolrStorage();   // usa solr storage per restituire oggetti (sembra lento...)
        $this->gateway = new FileSystem();      // scrive cache sul filesystem (cluster safe)
    }

    /**
     * @param $content
     * @param bool $ignorePolicies
     *
     * @return array
     * @throws ForbiddenException
     */
    public function read($content, $ignorePolicies = false)
    {
        if (!$content instanceof Content) {
            $content = $this->gateway->loadContent($content);
        }

        if (!$ignorePolicies && !$content->canRead()) {
            throw new ForbiddenException($content, 'read');
        }

        return $this->currentEnvironmentSettings->filterContent($content);
    }

    public function create($payload, $ignorePolicies = false)
    {
        $createStruct = $this->currentEnvironmentSettings->instanceCreateStruct($payload);
        $createStruct->validate($ignorePolicies);
        $publicationProcess = new PublicationProcess($createStruct);
        $contentId = $publicationProcess->publish();

        $this->currentEnvironmentSettings->afterCreate($contentId, $createStruct);

        return array(
            'message' => 'success',
            'method' => 'create',
            'content' => (array)$this->read($contentId, $ignorePolicies)
        );
    }

    public function update($payload, $ignorePolicies = false)
    {
        $updateStruct = $this->currentEnvironmentSettings->instanceUpdateStruct($payload);
        $updateStruct->validate($ignorePolicies);
        $publicationProcess = new PublicationProcess($updateStruct);
        $contentId = $publicationProcess->publish();

        $this->currentEnvironmentSettings->afterUpdate($contentId, $updateStruct);

        return array(
            'message' => 'success',
            'method' => 'update',
            'content' => (array)$this->read($contentId, $ignorePolicies)
        );
    }

    public function createUpdate($payload, $ignorePolicies = false){
        try {
            $result = $this->create($payload, $ignorePolicies);
        } catch (DuplicateRemoteIdException $e) {
            $result = $this->update($payload, $ignorePolicies);
        }

        return $result;
    }

    public function delete($data)
    {
        return 'todo';
    }

    /**
     * @return EnvironmentSettings
     */
    public function getCurrentEnvironmentSettings()
    {
        return $this->currentEnvironmentSettings;
    }

    /**
     * @param EnvironmentSettings $currentEnvironmentSettings
     *
     * @return $this
     */
    public function setEnvironment(EnvironmentSettings $currentEnvironmentSettings)
    {
        $this->currentEnvironmentSettings = $currentEnvironmentSettings;
        return $this;
    }

    /**
     * Alias of setEnvironment method
     *
     * @return ContentRepository
     */
    public function setCurrentEnvironmentSettings(EnvironmentSettings $environmentSettings)
    {
        return $this->setEnvironment($environmentSettings);
    }

    /**
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param Gateway $gateway
     */
    public function setGateway(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }


}
