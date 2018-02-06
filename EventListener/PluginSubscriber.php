<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;

/**
 * Class PluginSubscriber
 * @package MauticPlugin\MauticEnhancerBundle\EventListener
 */
class PluginSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['buildCustomFields', 0],
        ];
    }

    /**
     * @param PluginIntegrationEvent $event
     */
    public function buildCustomFields(PluginIntegrationEvent $event)
    {
        $integration = $event->getIntegration();
        if ($integration && method_exists($integration, 'buildEnhancerFields')) {
            $integration->buildEnhancerFields();
        }
    }
}
