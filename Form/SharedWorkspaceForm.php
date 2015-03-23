<?php

namespace FormaLibre\InvoiceBundle\Form;

use FormaLibre\InvoiceBundle\Entity\Product;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;

class SharedWorkspaceForm extends AbstractType
{
    private $product;
    private $router;
    private $em;
    private $translator;

    public function __construct(
        Product $product,
        $router,
        $em,
        $translator
    )
    {
        $this->product = $product;
        $this->router = $router;
        $this->em = $em;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $this->product;
        $details = $product->getDetails();
        $detailsInfo = $this->translator->trans(
            'SHARE_WS_DESCRIPTION_PAYPAL',
            array('%resources' => $details['max_resources'], '%users%' => $details['max_users'], '%storage%' => $details['max_storage']),
            'invoice'
        );

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
                'expanded' => true,
                'multiple' => false
            )
        );

        $builder
            ->add(
                'payment',
                'jms_choose_payment_method',
                array(
                    'label' => ' ', //fuck you label
                    'amount'   => 0,
                    'currency' => 'EUR',
                    'default_method' => 'payment_paypal',
                    'predefined_data' => array(
                        'label' => 'test',
                        'paypal_express_checkout' => array(
                            'label' => 'checkout',
                            'return_url' => $this->router->generate('workspace_product_payment_complete', array(
                                'order' => $this->product->getCode(),
                            ), true),
                            'cancel_url' => $this->router->generate('workspace_product_payment_cancel', array(
                                'order' => $this->product->getCode(),
                            ), true),
                            'checkout_params' => array(
                                'L_PAYMENTREQUEST_0_DESC0' => $detailsInfo,
                                'L_PAYMENTREQUEST_0_QTY0' => '1',
                                'L_PAYMENTREQUEST_0_AMT0'=> 0
                            )
                        )
                    )
                )
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        $amount = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($data['price'])->getPrice();
        $options = $form->get('payment')->getConfig()->getOptions();
        $options['amount'] = $amount;
        $options['predefined_data']['paypal_express_checkout']['checkout_params']['L_PAYMENTREQUEST_0_AMT0'] = $amount;
        $form->remove('payment');
        $form->add(
            'payment',
            'jms_choose_payment_method',
            $options
        );
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
