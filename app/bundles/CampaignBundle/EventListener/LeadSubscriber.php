<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * LeadSubscriber constructor.
     *
     * @param CampaignModel $campaignModel
     * @param LeadModel     $leadModel
     */
    public function __construct(CampaignModel $campaignModel, LeadModel $leadModel)
    {
        $this->campaignModel = $campaignModel;
        $this->leadModel     = $leadModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_LIST_BATCH_CHANGE => ['onLeadListBatchChange', 0],
            LeadEvents::LEAD_LIST_CHANGE       => ['onLeadListChange', 0],
            LeadEvents::TIMELINE_ON_GENERATE   => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE        => ['onLeadMerge', 0]
        ];
    }

    /**
     * Add/remove leads from campaigns based on batch lead list changes
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListBatchChange(ListChangeEvent $event)
    {
        static $campaignLists = [], $listCampaigns = [], $campaignReferences = [];

        $leads  = $event->getLeads();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';
        $em     = $this->em;

        //get campaigns for the list
        if (!isset($listCampaigns[$list->getId()])) {
            $listCampaigns[$list->getId()] = $this->campaignModel->getRepository()->getPublishedCampaignsByLeadLists($list->getId());
        }

        $leadLists = $em->getRepository('MauticLeadBundle:LeadList')->getLeadLists($leads, true, true);

        if (!empty($listCampaigns[$list->getId()])) {
            foreach ($listCampaigns[$list->getId()] as $c) {
                if (!isset($campaignReferences[$c['id']])) {
                    $campaignReferences[$c['id']] = $em->getReference('MauticCampaignBundle:Campaign', $c['id']);
                }

                if ($action == 'added') {
                    $this->campaignModel->addLeads($campaignReferences[$c['id']], $leads, false, true);
                } else {
                    if (!isset($campaignLists[$c['id']])) {
                        $campaignLists[$c['id']] = [];
                        foreach ($c['lists'] as $l) {
                            $campaignLists[$c['id']][] = $l['id'];
                        }
                    }

                    $removeLeads = [];
                    foreach ($leads as $l) {
                        $lists = (isset($leadLists[$l])) ? $leadLists[$l] : [];
                        if (array_intersect(array_keys($lists), $campaignLists[$c['id']])) {
                            continue;
                        } else {
                            $removeLeads[] = $l;
                        }
                    }

                    $this->campaignModel->removeLeads($campaignReferences[$c['id']], $removeLeads, false, true);
                }
            }
        }

        // Save memory with batch processing
        unset($event, $em, $model, $leads, $list, $listCampaigns, $leadLists);
    }

    /**
     * Add/remove leads from campaigns based on lead list changes
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListChange(ListChangeEvent $event)
    {
        $lead   = $event->getLead();
        $list   = $event->getList();
        $action = $event->wasAdded() ? 'added' : 'removed';
        $repo   = $this->campaignModel->getRepository();

        //get campaigns for the list
        $listCampaigns = $repo->getPublishedCampaignsByLeadLists($list->getId());

        $leadLists   = $this->leadModel->getLists($lead, true);
        $leadListIds = array_keys($leadLists);

        // If the lead was removed then don't count it
        if ($action == 'removed') {
            $key = array_search($list->getId(), $leadListIds);
            unset($leadListIds[$key]);
        }

        if (!empty($listCampaigns)) {
            foreach ($listCampaigns as $c) {
                $campaign = $this->em->getReference('MauticCampaignBundle:Campaign', $c['id']);

                if (!isset($campaignLists[$c['id']])) {
                    $campaignLists[$c['id']] = array_keys($c['lists']);
                }

                if ($action == 'added') {
                    $this->campaignModel->addLead($campaign, $lead);
                } else {
                    if (array_intersect($leadListIds, $campaignLists[$c['id']])) {

                        continue;
                    }

                    $this->campaignModel->removeLead($campaign, $lead);
                }

                unset($campaign);
            }
        }
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'campaign.event';
        $eventTypeName = $this->translator->trans('mautic.campaign.triggered');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {

            return;
        }

        $lead = $event->getLead();

        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $logRepository */
        $logRepository = $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
        $logs          = $logRepository->getLeadLogs($lead->getId(), $event->getQueryOptions());
        $eventSettings = $this->campaignModel->getEvents();

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            foreach ($logs['results'] as $log) {
                // Hide this from the time line all together
                if (!empty($log['metadata']['failed'])) {
                    $event->subtractFromCounter($eventTypeKey);

                    continue;
                }

                $template = (!empty($eventSettings['action'][$log['type']]['timelineTemplate']))
                    ? $eventSettings['action'][$log['type']]['timelineTemplate'] : 'MauticCampaignBundle:SubscribedEvents\Timeline:index.html.php';

                $event->addEvent(
                    [
                        'event'           => $eventTypeKey,
                        'eventLabel'      => [
                            'label' => $log['event_name'].' / '.$log['campaign_name'],
                            'href'  => $this->router->generate(
                                'mautic_campaign_action',
                                ['objectAction' => 'view', 'objectId' => $log['campaign_id']]
                            )
                        ],
                        'eventType'       => $eventTypeName,
                        'timestamp'       => $log['dateTriggered'],
                        'extra'           => [
                            'log' => $log
                        ],
                        'contentTemplate' => $template,
                        'icon'            => 'fa-clock-o'
                    ]
                );
            }
        }
    }

    /**
     * Update records after lead merge
     *
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticCampaignBundle:LeadEventLog')->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());

        $this->em->getRepository('MauticCampaignBundle:Lead')->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}