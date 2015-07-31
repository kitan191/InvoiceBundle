<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Claroline\CoreBundle\Validator\Constraints\FileSize;

class SharedWorkspaceCreationForm extends AbstractType
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
            'pretty_name',
            'text',
            array(
                'required' => true,
                'label' => 'pretty_name',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'max_users',
            'number',
            array(
                'required' => true,
                'label' => 'max_users',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'max_resources',
            'number',
            array(
                'required' => true,
                'label' => 'max_resources',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'max_storage',
            'text',
            array(
                'required' => true,
                'label' => 'max_storage',
                'constraints' => array(
                    new NotBlank(),
                    new FileSize()
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
