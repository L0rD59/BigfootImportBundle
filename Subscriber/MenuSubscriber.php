<?php

namespace Bigfoot\Bundle\ImportBundle\Subscriber;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Bigfoot\Bundle\CoreBundle\Event\MenuEvent;

/**
 * Menu Subscriber
 */
class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorage
     */
    private $security;

    /**
     * @param TokenStorage $security
     */
    public function __construct(TokenStorage $security)
    {
        $this->security = $security;
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            MenuEvent::GENERATE_MAIN => array('onGenerateMain', 2)
        );
    }

    /**
     * @param GenericEvent $event
     */
    public function onGenerateMain(GenericEvent $event)
    {
        $builder = $event->getSubject();

        $builder
            ->addChild(
                'flux',
                array(
                    'label'          => 'Flux',
                    'url'            => '#',
                    'attributes' => array(
                        'class' => 'parent',
                    ),
                    'linkAttributes' => array(
                        'class' => 'dropdown-toggle',
                        'icon'  => 'refresh',
                    )
                ),
                array(
                    'children-attributes' => array(
                        'class' => 'submenu'
                    )
                )
            )
            ->addChildFor(
                'flux',
                'imports',
                array(
                    'label'  => 'Imports',
                    'route'  => 'admin_datasource',
                    'extras' => array(
                        'routes' => array(
                            'admin_datasource_new',
                            'admin_datasource_edit'
                        )
                    ),
                    'linkAttributes' => array(
                        'icon' => 'level-down',
                    )
                )
            );
    }
}
