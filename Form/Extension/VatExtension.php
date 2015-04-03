<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FormaLibre\InvoiceBundle\Validator\Constraints\Vat;

class VatExtension extends AbstractTypeExtension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->translator = $container->get('translator');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $form = $event->getForm();
            if ($form->has('formalibre_vat')) {
                $form->remove('formalibre_vat');
                $form->add(
                    'formalibre_vat',
                    'text',
                    array(
                        'label'  => $this->translator->trans('formalibre_vat', array(), 'invoice'),
                        'mapped' => false,
                        'required' => false,
                        'constraints' => array(new Vat()),
                        'attr' => array('facet' => 'company')
                    )
                );
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
