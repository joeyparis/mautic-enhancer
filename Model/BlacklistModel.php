<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerBlacklist;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerBlacklistRepository;
use MauticPlugin\MauticEnhancerBundle\Integration\BlacklistIntegration;

class BlacklistModel extends AbstractCommonModel
{
    /** @var string The URL for Blacklist GET requests */
    protected $endpoint;

    /** @var string The API key provided for Blacklist */
    protected $key;

    /**
     * @param BlacklistIntegration $integration
     */
    public function setup(BlacklistIntegration $integration)
    {
        $settings       = $integration->getIntegrationSettings();
        $keys           = $integration->getKeys();
        $this->endpoint = $settings['endpoint'];
        $this->key      = $keys['key'];
    }

    /**
     * Gets from local cache and/or calls the Blacklist serivice.
     * See documentation at http://developer.theblacklist.click
     *
     * @param string  $phone
     * @param integer $ageMinutes
     * @param bool    $cacheOnly Only pull cache records.
     *
     * @return string
     *
     * @return PluginEnhancerBlacklist
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRecord($phone, $ageMinutes, $cacheOnly = false)
    {
        /** @var PluginEnhancerBlacklist $record */
        $record = $this->getRepository()->findByPhone($phone);

        if (null === $record || $record->getDateAdded() < (time() - ($ageMinutes * 60))) {

            // Do not make the API request if cacheOnly.
            if (!$cacheOnly) {
                return false;
            }

            // Blacklist service does not support E164 standard.
            $uri = rtrim($this->endpoint, '/').'/'.trim($this->key).'/response/json/phone/'.ltrim('+', $phone);
            try {
                $httpClient = new Client();
                $response   = $httpClient->request('GET', $uri);
                $result     = json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                $this->logger->error('Blacklist Enhancer: '.$e->getMessage());

                return false;
            }
            if (null === $record) {
                $record = new PluginEnhancerBlacklist();
                $record->setPhone($phone);
            }
            if ($result['sid']) {
                $record->setSid($result['sid']);
            }
            if ($result['code']) {
                $record->setCode($result['code']);
            }
            if ($result['blacklisted']) {
                $record->setBlacklisted($result['blacklisted']);
            }
            if ($result['wireless']) {
                $record->setWireless($result['wireless']);
            }
            $record->setDateAdded(new \DateTime());
            $this->getRepository()->saveEntity($record);
        }

        return $record;
    }

    /**
     * @return PluginEnhancerBlacklistRepository|EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return PluginEnhancerBlacklist::class;
    }
}
