<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Component\Form\FormError;

/**
 * Class SysinfoController
 */
class SysinfoController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($page = 1)
    {
        if (!$this->user->isAdmin() || $this->coreParametersHelper->getParameter('sysinfo_disabled')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\ConfigBundle\Model\SysinfoModel $model */
        $model   = $this->getModel('config.sysinfo');
        $phpInfo = $model->getPhpInfo();
        $folders = $model->getFolders();
        $log     = $model->getLogTail();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'phpInfo' => $phpInfo,
                'folders' => $folders,
                'log'     => $log
            ),
            'contentTemplate' => 'MauticConfigBundle:Sysinfo:index.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_sysinfo_index',
                'mauticContent' => 'sysinfo',
                'route'         => $this->generateUrl('mautic_sysinfo_index')
            )
        ));
    }
}
