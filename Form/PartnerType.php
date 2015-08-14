<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'required' => true,
                'label' => 'name',
                'translation_domain' => 'platform',
                'constraints' => array(new NotBlank())
            )
        );
        
        $builder->add(
            'code',
            'text',
            array(
                'required' => true,
                'label' => 'code',
                'translation_domain' => 'platform',
                'constraints' => array(new NotBlank())
            )
        );
    }

    public function getName()
    {
        return 'partner_creation_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
