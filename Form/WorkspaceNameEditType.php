<?php

namespace FormaLibre\InvoiceBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkspaceNameEditType extends AbstractType
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'required' => true,
                'mapped' => false,
                'label' => 'name',
                'translation_domain' => 'platform',
                'data' => $this->name,
                'constraints' => array(new NotBlank())
            )
        );
    }

    public function getName()
    {
        return 'workspace_name_edit_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
