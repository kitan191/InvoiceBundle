<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupportTechType extends AbstractType
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
            'title',
            'text',
            array(
                'required' => true,
                'label' => 'title',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'description',
            'text',
            array(
                'required' => true,
                'label' => 'description',
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
