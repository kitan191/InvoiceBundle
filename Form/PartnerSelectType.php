<?php

namespace FormaLibre\InvoiceBundle\Form;

use FormaLibre\InvoiceBundle\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PartnerSelectType extends AbstractType
{
    private $partner;

    public function __construct(Partner $partner = null)
    {
        $this->partner = $partner;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'partner',
            'entity',
            array(
                'required' => false,
                'mapped' => false,
                'data' => $this->partner,
                'class' => 'FormaLibreInvoiceBundle:Partner',
                'property' => 'name',
                'choice_translation_domain' => true
            )
        );
    }

    public function getName()
    {
        return 'partner_selection_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
