<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amalyuhin
 * Date: 04.03.13
 * Time: 16:56
 * To change this template use File | Settings | File Templates.
 */

namespace Wealthbot\ClientBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Wealthbot\ClientBundle\Entity\AccountContribution;
use Wealthbot\ClientBundle\Entity\ClientAccount;
use Wealthbot\ClientBundle\Repository\ClientAccountRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class TransferFundingFormEventSubscriber implements EventSubscriberInterface
{
    protected $factory;
    protected $em;
    protected $clientAccount;

    public function __construct(FormFactoryInterface $factory, EntityManager $em, ClientAccount $clientAccount)
    {
        $this->factory = $factory;
        $this->em = $em;
        $this->clientAccount = $clientAccount;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_BIND     => 'preBind',
        );
    }

    public function preSetData(FormEvent $event)
    {
        /** @var $data AccountContribution */
        $data = $event->getData();
        $form = $event->getForm();

        $this->addContributionYearField($form);

        $date = array('month' => '', 'day' => '');

        if ($data && $data->getStartTransferDate()) {
            $startTransferDate = $data->getStartTransferDate();

            $date['month'] = $startTransferDate->format('m');
            $date['day'] = $startTransferDate->format('d');
        }

        $this->updateStartTransferDate($form, $date);
    }

    protected function addContributionYearField(FormInterface $form)
    {
        if ($this->clientAccount->isRothIraType()) {
            $form->add($this->factory->createNamed('contribution_year', 'text', null, array('required' => false)));
        }
    }

    public function preBind(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (array_key_exists('start_transfer_date_month', $data) && array_key_exists('start_transfer_date_day', $data)) {
            $this->updateStartTransferDate($form, array(
                    'day' => $data['start_transfer_date_day'],
                    'month' => $data['start_transfer_date_month']
                )
            );
        }
    }

    private function updateStartTransferDate(FormInterface $form, array $date)
    {
        $form->add($this->factory->createNamed('start_transfer_date_month', 'text', $date['month'], array(
                'attr' => array('value' => $date['month']),
                'property_path' => false,
                'required' => false
            )))
            ->add($this->factory->createNamed('start_transfer_date_day', 'text', $date['day'], array(
                'attr' => array('value' => $date['day']),
                'property_path' => false,
                'required' => false
            )))
        ;
    }
}