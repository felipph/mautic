<?php
/**
 * @package     Mautic Focus Bundle
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Mautic Focus',
    'description' => 'Drive visitor\'s focus on your website with Mautic Focus',
    'version'     => '1.0',
    'author'      => 'Mautic, Inc',

    'routes' => [
        'main'   => [
            'mautic_focus_pagetoken_index' => [
                'path'       => '/focus/pagetokens/{page}',
                'controller' => 'MauticFocusBundle:SubscribedEvents\BuilderToken:index',
            ],
            'mautic_focus_index'           => [
                'path'       => '/focus/{page}',
                'controller' => 'MauticFocusBundle:Focus:index',
            ],
            'mautic_focus_action'          => [
                'path'       => '/focus/{objectAction}/{objectId}',
                'controller' => 'MauticFocusBundle:Focus:execute',
            ],
        ],
        'public' => [
            'mautic_focus_generate' => [
                'path'       => '/focus/{id}.js',
                'controller' => 'MauticFocusBundle:Public:generate',
            ],
            'mautic_focus_pixel'    => [
                'path'       => '/focus/{id}/viewpixel.gif',
                'controller' => 'MauticFocusBundle:Public:viewPixel',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.focus.subscriber.form_bundle' => [
                'class'     => 'MauticPlugin\MauticFocusBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.focus.model.focus',
                ],
            ],
            'mautic.focus.subscriber.page_bundle' => [
                'class'     => 'MauticPlugin\MauticFocusBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.focus.model.focus',
                    'router',
                ],
            ],
            'mautic.focus.subscriber.stat'        => [
                'class'     => 'MauticPlugin\MauticFocusBundle\EventListener\StatSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.focus.model.focus',
                ],
            ],
            'mautic.focus.subscriber.focus'       => [
                'class'     => 'MauticPlugin\MauticFocusBundle\EventListener\FocusSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'router',
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
        ],
        'forms'  => [
            'mautic.focus.form.type.color'             => [
                'class' => 'MauticPlugin\MauticFocusBundle\Form\Type\ColorType',
                'alias' => 'focus_color',
            ],
            'mautic.focus.form.type.content'           => [
                'class' => 'MauticPlugin\MauticFocusBundle\Form\Type\ContentType',
                'alias' => 'focus_content',
            ],
            'mautic.focus.form.type.focus'             => [
                'class'     => 'MauticPlugin\MauticFocusBundle\Form\Type\FocusType',
                'alias'     => 'focus',
                'arguments' => 'mautic.security',
            ],
            'mautic.focus.form.type.entity_properties' => [
                'class' => 'MauticPlugin\MauticFocusBundle\Form\Type\PropertiesType',
                'alias' => 'focus_entity_properties',
            ],
            'mautic.focus.form.type.properties'        => [
                'class' => 'MauticPlugin\MauticFocusBundle\Form\Type\FocusPropertiesType',
                'alias' => 'focus_properties',
            ],
        ],
        'models' => [
            'mautic.focus.model.focus' => [
                'class'     => 'MauticPlugin\MauticFocusBundle\Model\FocusModel',
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.page.model.trackable',
                    'mautic.helper.templating',
                ],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.focus' => [
                'route'  => 'mautic_focus_index',
                'access' => 'plugin:focus:items:view',
                'parent' => 'mautic.core.channels',
            ],
        ],
    ],

    'categories' => [
        'plugin:focus' => 'mautic.focus',
    ],

    'parameters' => [
        'website_snapshot_url' => 'https://mautic.net/api/snapshot',
        'website_snapshot_key' => '',
    ],
];