<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreditSupportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'code',
            'text',
            array(
                'required' => true,
                'label' => 'code',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'credit_amount',
            'number',
            array(
                'required' => true,
                'label' => 'credit_amount',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
    }

    public function getName()
    {
        return 'sws_product_creation_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
