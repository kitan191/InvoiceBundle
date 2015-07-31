<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Claroline\CoreBundle\Validator\Constraints\FileSize;

class SharedWorkspaceEditForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'pretty_name',
            'text',
            array(
                'required' => true,
                'label' => 'pretty_name',
                'mapped' => false
            )
        );
    }

    public function getName()
    {
        return 'sws_product_creation_edit_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
