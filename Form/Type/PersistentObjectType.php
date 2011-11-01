<?php

/*
 * Doctrine CouchDB Bundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Doctrine\Bundle\CouchDBBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\AbstractType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\CouchDBBundle\ChoiceList\EntityChoiceList;
use Doctrine\Bundle\CouchDBBundle\Form\EventListener\MergeCollectionListener;
use Doctrine\Bundle\CouchDBBundle\Form\DataTransformer\PersistentObjectToArrayTransformer;
use Doctrine\Bundle\CouchDBBundle\Form\DataTransformer\PersistentObjectToIdTransformer;

abstract class PersistentObjectType extends AbstractType
{
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            $builder
                ->addEventSubscriber(new MergeCollectionListener())
                ->prependClientTransformer(new PersistentObjectToArrayTransformer($options['choice_list']))
            ;
        } else {
            $builder->prependClientTransformer(new PersistentObjectToIdTransformer($options['choice_list']));
        }
    }

    abstract protected function getObjectManagerKey();

    /**
     * @return PersistentObjectChoiceList
     */
    abstract protected function createDefaultChoiceList($options);

    public function getDefaultOptions(array $options)
    {
        $objectManagerKey = $this->getObjectManagerKey();
        $defaultOptions = array(
            $objectManagerKey   => null,
            'class'             => null,
            'property'          => null,
            'query_builder'     => null,
            'choices'           => array(),
        );

        $options = array_replace($defaultOptions, $options);

        if (!isset($options['choice_list'])) {
            $defaultOptions['choice_list'] = $this->createDefaultChoiceList($options);
        }

        return $defaultOptions;
    }

    public function getParent(array $options)
    {
        return 'choice';
    }
}
