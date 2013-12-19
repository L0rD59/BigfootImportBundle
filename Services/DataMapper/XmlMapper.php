<?php
namespace Bigfoot\Bundle\ImportBundle\Services\DataMapper;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Yaml;

/**
 * Class XmlMapper
 *
 * Implements import XMl data in project database
 *
 * @package Bigfoot\Bundle\ImportBundle\Services\DataMapper
 */
class XmlMapper
{
    private $controller;
    private $mappingInfo;

    /**
     * Construct an XmlParser
     *
     * @param Controller $controller  Controller using the mapper
     * @param string     $mappingFile URl to yaml file contains mapping informations
     */
    public function __construct(Controller $controller, $mappingFile)
    {
        $this->controller = $controller;

        // Chargement de la configuration du mapping
        $this->mappingInfo = Yaml::parse(file_get_contents($mappingFile));

        if ($this->mappingInfo == null || !is_array($this->mappingInfo)) {
            echo "Mapping schema isn't valid.\nImport will not be possible without a valid mapping schema.\n";
        }
    }

    /**
     * Start mapping
     *
     * @param \SimpleXMLElement $xmlElement XmlElement to import
     *
     * @return mixed Return null if mappingInfo isn't valid otherwise return an object
     */
    public function map(\SimpleXMLElement $xmlElement)
    {

        if ($this->mappingInfo == null || !is_array($this->mappingInfo)) {
            echo "Mapping schema isn't valid.\nPlease load a valid mapping schema before performing an import.\n";
            return null;
        } else {
            return $this->xmlElementToEntity($this->mappingInfo, $xmlElement);
        }
    }

    /**
     * Internal recursive function to perform mapping.
     * Perform mapping for parent entity, and launch recursively for all children entity
     *
     * @param array             $mapping
     * @param \SimpleXmlElement $xmlElement
     * @param $parentElement
     *
     * @return mixed
     */
    private function xmlElementToEntity(array $mapping, \SimpleXMLElement $xmlElement, $parentElement = null)
    {
        $em = $this->controller->getDoctrine()->getManager();

//        var_dump($xmlElement);die;

        foreach ($mapping as $objectName => $description) {
            if (is_array($description)) {

                $oldObject = null;
                $$objectName = null;
                if (isset($description['key'])) {
                    // We attempt to retrieve an existing object corresponding to XML data
                    if ((string)$description['key']['xmlKey'] == '@uniqueId') {
                        $uniqueId = $xmlElement->xpath('@id');
                        $nodeParent = $xmlElement->xpath('../..');
                        $key = array(0 => $nodeParent[0]->getName().'-'.(string)$uniqueId[0]);
//                        echo $nodeParent[0]->getName().'-'.(string)$uniqueId[0];
                    }
                    else {
                        $key = $xmlElement->xpath($description['key']['xmlKey']);
                    }
                    // If xmlKey not found in XMl Data, there's no object to create. So we return null
                    if (!isset($key[0])) {
                        return null;
                    }
                    $oldObject = $em->getRepository($description['repository'])->findOneBy(array($description['key']['keyName'] => $key[0]));
                }

                if (!is_null($oldObject)) {
                    $$objectName = $oldObject;
                } else {
                    $$objectName = new $description['class']();
                }

                foreach ($description['mapping'] as $function => $xpath) {
                    if (is_array($xpath)) {
                        if (isset($xpath['languages'])) {
                            $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                            foreach ($xpath['languages'] as $language => $element) {
                                $translation = $xmlElement->xpath($element);
                                if (count($translation) > 0) {
                                    $repository->translate($$objectName, $function, $language, $translation[0]);
                                }
                            }
                        } else {
                            foreach ($xpath as $type => $typeDescription) {
                                // Nettoyage avant import
                                if (isset($typeDescription['clear'])) {
                                    if (is_array($typeDescription['clear'])) {
                                        $oldElements = $$objectName->$typeDescription['clear']['getFunction']();
                                        if ($oldElements != null) {
                                            foreach ($oldElements as $oldElement) {
                                                $$objectName->$typeDescription['clear']['removeFunction']($oldElement);
                                                //$em->remove($oldElement);
                                            }
                                        }
                                    }
                                }

                                if (count($xmlElement->xpath($typeDescription['xpath'])) == 0) {
                                    //$$objectName->$function(null);
                                } else {
                                    foreach ($xmlElement->xpath($typeDescription['xpath']) as $childElement) {
                                        $myChild = $this->xmlElementToEntity(array($type => $typeDescription), $childElement, $$objectName);
                                        if ($myChild != null) {
                                            $$objectName->$function($myChild);
                                        }
                                    }
                                }
                            }
                        }
                    } else {

                        if ((string)$xpath == '@uniqueId') {
                            $uniqueId = $xmlElement->xpath('@id');
                            $nodeParent = $xmlElement->xpath('../..');
                            $$objectName->$function((string)$nodeParent[0]->getName());
                            $$objectName->setUniqueId($nodeParent[0]->getName().'-'.(string)$uniqueId[0]);
                        }
                        else {
                            if ( count($xmlElement->xpath($xpath)) == 0) {
                                $$objectName->$function(null);
                            } else {
                                $path = $xmlElement->xpath($xpath);
                                $$objectName->$function((string) $path[0]);
                            }
                        }
                    }
                }

                if (isset($description['relationToParent']) && is_string($description['relationToParent'])) {
                    $$objectName->$description['relationToParent']($parentElement);
                }

                $em->persist($$objectName);
                $em->flush();
                $em->refresh($$objectName);

                return $$objectName;

            } else {
                return null;
            }
        }
    }
}