<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PriceSolutionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'price',
            'number',
            array(
                'required' => true,
                'label' => 'price',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'monthDuration',
            'integer',
            array(
                'required' => false,
                'label' => 'duration'
            )
        );
    }

    public function getName()
    {
        return 'price_solution_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
