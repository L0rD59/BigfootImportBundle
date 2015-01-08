<?php

namespace Bigfoot\Bundle\ImportBundle\Translation;

use Doctrine\Common\Persistence\Proxy;

/**
 * Class DataTranslationQueue
 * @package Bigfoot\Bundle\ImportBundle\Translation
 */
class DataTranslationQueue
{
    /** @var array */
    private $queue = array();

    /**
     * @param array $queue
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param object $entity
     * @param string $property
     * @param string $locale
     * @param string $value
     * @return $this
     */
    public function add($entity, $property, $locale, $value)
    {
        if (is_object($entity)) {
            $entityClass = ($entity instanceof Proxy) ? get_parent_class($entity) : get_class($entity);
        } else {
            return null;
        }

        if (!isset($this->queue[$entityClass])) {
            $this->queue[$entityClass] = array();
        }

        if (!isset($this->queue[$entityClass][spl_object_hash($entity)])) {
            $this->queue[$entityClass][spl_object_hash($entity)] = array();
        }

        if (!isset($this->queue[$entityClass][spl_object_hash($entity)][$locale])) {
            $this->queue[$entityClass][spl_object_hash($entity)][$locale] = array();
        }

        $this->queue[$entityClass][spl_object_hash($entity)][$locale][$property] = array(
            'entity' => $entity,
            'content' => $value
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->queue = array();

        return $this;
    }
}
