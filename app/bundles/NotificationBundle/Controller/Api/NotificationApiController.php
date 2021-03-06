<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\NotificationBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class NotificationApiController.
 */
class NotificationApiController extends CommonApiController
{
    /**
     * Receive Web Push subscription request.
     *
     * @return JsonResponse
     */
    public function subscribeAction()
    {
        $osid = $this->request->get('osid');

        if ($osid) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->getModel('lead');

            $currentLead = $leadModel->getCurrentLead();

            $currentLead->addPushIDEntry($osid);

            $leadModel->saveEntity($currentLead);

            return new JsonResponse(['success' => true], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }
}
