<?php

namespace FormaLibre\InvoiceBundle\Form;

use FormaLibre\InvoiceBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\EntityRepository;

class SharedWorkspaceForm extends AbstractType
{
    private $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $this->product;
        
        $builder->add(
            'price',
            'entity',
            array(
                'label' => ' ', //no label
                'class' => 'FormaLibreInvoiceBundle:PriceSolution',
                'query_builder' => function(EntityRepository $er) use ($product) {
                    return $er->createQueryBuilder('ps')
                        ->join('ps.product', 'p')
                        ->where('p.id = ' . $product->getId());
                },
                'expanded' => false,
                'multiple' => false
            )
        );
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($form->has('captcha')) $form->remove('captcha');
    }

    public function getName()
    {
        return 'shared_workspace_product_form_' . $this->product->getCode();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('translation_domain' => 'invoice')
        );
    }
}
