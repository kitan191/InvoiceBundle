<?php

namespace FormaLibre\InvoiceBundle\Payment;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class BankTransferType extends AbstractType
{
   /**
    * @param FormBuilderInterface $builder The builder
    * @param array $options Options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
    }

    /**
    * @return string
    */
    public function getName()
    {
        return 'bank_transfer';
    }
}
