<?php

namespace FormaLibre\InvoiceBundle\Form;

use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\Order;
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
    private $order;
    private $swsId;
    private $vatManager;
    private $communication;

    public function __construct(
        Product $product,
        $router,
        $em,
        $translator,
        Order $order,
        $vatManager,
        $swsId = 0
    )
    {
        $this->product = $product;
        $this->router = $router;
        $this->em = $em;
        $this->translator = $translator;
        $this->order = $order;
        $this->swsId = $swsId;
        $this->vatManager = $vatManager;
        $this->communication = $this->getCommunication();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $this->product;
        $details = $product->getDetails();
        $detailsInfo = $this->translator->trans(
            'SHARE_WS_DESCRIPTION_PAYPAL',
            array(
                '%resources' => $details['max_resources'],
                '%users%' => $details['max_users'],
                '%storage%' => $details['max_storage'],
                '%code%' => $product->getCode()
            ),
            'invoice'
        );

        $returnSuccessUrl = $this->router->generate(
            'workspace_product_payment_complete',
            array('order' => $this->order->getId(), 'swsId' => $this->swsId), true
        );

        $pendingUrl = $this->router->generate(
            'workspace_product_payment_pending',
            array('order' => $this->order->getId()), true
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
                    'currency' => 'USD',
                    'allowed_methods' => array('bank_transfer', 'paypal_express_checkout'),
                    'default_method' => 'payment_paypal',
                    'predefined_data' => array(
                        'label' => 'test',
                        'paypal_express_checkout' => array(
                            'label' => '',
                            'return_url' => $returnSuccessUrl,
                            'cancel_url' => $this->router->generate('workspace_product_payment_cancel', array(
                                'order' => $this->product->getCode(),
                            ), true),
                            'checkout_params' => array(
                                //'L_PAYMENTREQUEST_0_AMT0' => 0,
                                'L_PAYMENTREQUEST_0_DESC0' => $detailsInfo,
                                'L_PAYMENTREQUEST_0_QTY0' => '1'
                            )
                        ),
                        'bank_transfer' => array(
                            'return_url' => $returnSuccessUrl,
                            'pending_url' => $pendingUrl,
                            'cancel_url' => $this->router->generate('workspace_product_payment_cancel', array(
                                'order' => $this->product->getCode(),
                            ), true),
                            'communication' => $this->communication
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
        $priceSolution = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($data['price']);
        $amount = $priceSolution->getPrice();
        $vat = $this->vatManager->getVat($amount);
        $totalAmount = $amount + $vat;
        $options = $form->get('payment')->getConfig()->getOptions();
        $options['amount'] = $totalAmount;

        $options['predefined_data']['paypal_express_checkout']['checkout_params']['L_PAYMENTREQUEST_0_AMT0'] = $amount;
        $options['predefined_data']['paypal_express_checkout']['checkout_params']['PAYMENTREQUEST_0_ITEMAMT'] = $amount;
        $options['predefined_data']['paypal_express_checkout']['checkout_params']['PAYMENTREQUEST_0_TAXAMT'] = $vat;
        $options['predefined_data']['paypal_express_checkout']['checkout_params']['PAYMENTREQUEST_0_AMT'] = $totalAmount;

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

    private function getCommunication()
    {
         $base = rand(1000000000, 9999999999);
         $ctrl = $base % 97;

         return "$base" + "$ctrl";
    }
}
