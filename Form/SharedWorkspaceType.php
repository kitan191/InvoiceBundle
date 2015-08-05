<?php

namespace FormaLibre\InvoiceBundle\Form;

use Claroline\CoreBundle\Validator\Constraints\FileSize;
use Doctrine\ORM\EntityRepository;
use FormaLibre\InvoiceBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SharedWorkspaceType extends AbstractType
{
    private $product;
    private $translator;

    public function __construct(TranslatorInterface $translator, Product $product = null)
    {
        $this->product = $product;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $priceSolutions = is_null($this->product) ? array() : $this->product->getPriceSolutions();
        $details = is_null($this->product) ? array() : $this->product->getDetails();
        $pricesDatas = array();
        $expirationDate = new \DateTime();
        $i = 0;

        foreach ($priceSolutions as $solution) {
            $pricesDatas[$i] = $solution->getMonthDuration() .
                ' ' .
                $this->translator->trans('months', array(), 'invoice');
            $i++;
        }
        $builder->add(
            'name',
            'text',
            array(
                'required' => true,
                'mapped' => false,
                'label' => 'name',
                'translation_domain' => 'platform',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'code',
            'text',
            array(
                'required' => true,
                'mapped' => false,
                'label' => 'code',
                'translation_domain' => 'platform',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'owner',
            'userpicker',
            array(
                'required' => true,
                'picker_title' => $this->translator->trans('select_owner', array(), 'invoice'),
                'label' => 'owner',
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'product',
            'entity',
            array(
                'label' => 'product',
                'class' => 'FormaLibreInvoiceBundle:Product',
                'choice_translation_domain' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.type = :type')
                        ->andWhere('p.isActivated = true')
                        ->setParameter('type', 'SHARED_WS')
                        ->orderBy('p.code', 'ASC');
                },
                'property' => 'code',
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,
                'required' => true
            )
        );
        $builder->add(
            'price',
            'choice',
            array(
                'label' => 'formula',
                'mapped' => false,
                'choices' => $pricesDatas,
                'multiple' => false,
                'required' => true
            )
        );
        $dateParams = array(
            'label' => 'expiration_date',
            'format' => 'dd-MM-yyyy',
            'widget' => 'single_text',
            'input' => 'datetime',
            'data' => $expirationDate,
            'attr' => array(
                'class' => 'datepicker input-small',
                'data-date-format' => 'dd-mm-yyyy',
                'autocomplete' => 'off'
            )
        );
        $builder->add(
            'expDate',
            'datepicker',
            $dateParams
        );
        $builder->add(
            'maxSize',
            'text',
            array(
                'required' => true,
                'label' => 'max_storage_size',
                'translation_domain' => 'platform',
                'data' => isset($details['max_storage']) ? $details['max_storage'] : null,
                'constraints' => array(
                    new NotBlank(),
                    new FileSize()
                )
            )
        );
        $builder->add(
            'maxUser',
            'integer',
            array(
                'required' => true,
                'label' => 'workspace_max_users',
                'translation_domain' => 'platform',
                'data' => isset($details['max_users']) ? $details['max_users'] : null,
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
        $builder->add(
            'maxRes',
            'integer',
            array(
                'required' => true,
                'label' => 'max_amount_resources',
                'translation_domain' => 'platform',
                'data' => isset($details['max_resources']) ? $details['max_resources'] : null,
                'constraints' => array(
                    new NotBlank()
                )
            )
        );
    }

    public function getName()
    {
        return 'shared_workspace_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'invoice'));
    }
}
