<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreditSupportEditType extends AbstractType
{
    //{"name":"Pack 100","nb_credits":100,"nb_hours":20,"saving":250}
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'required' => true,
                'label' => 'name',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'nb_hours',
            'number',
            array(
                'required' => true,
                'label' => 'nb_hours',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'nb_credits',
            'number',
            array(
                'required' => true,
                'label' => 'nb_credits',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'saving',
            'number',
            array(
                'required' => true,
                'label' => 'saving',
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
