<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PosBundle\Event\Listener;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Component\Pos\Domain\Pos;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;

/**
 * Class PosSerializationListener.
 */
class PosSerializationListener implements EventSubscriberInterface
{
    /**
     * @var TransactionDetailsRepository
     */
    protected $transactionDetailsRepository;

    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * PosSerializationListener constructor.
     *
     * @param TransactionDetailsRepository $transactionDetailsRepository
     * @param SettingsManager              $settingsManager
     */
    public function __construct(TransactionDetailsRepository $transactionDetailsRepository, SettingsManager $settingsManager)
    {
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->settingsManager = $settingsManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'),
        );
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var Pos $pos */
        $pos = $event->getObject();

        if ($pos instanceof Pos) {
            $currency = $this->settingsManager->getSettingByKey('currency');
            $currency = $currency ? $currency->getValue() : 'PLN';
            $event->getVisitor()->addData('currency', $currency);
            //            $transactions = $this->transactionDetailsRepository->findBy(['posId' => $pos->getPosId()->__toString()]);
            //            $event->getVisitor()->addData('transactionsCount', $this->countTransactions($transactions));
            //            $event->getVisitor()->addData('transactionValue', $this->countTransactionsValues($transactions));
        }
    }

    protected function countTransactions(array $transactions)
    {
        return count($transactions);
    }

    protected function countTransactionsValues(array $transactions)
    {
        return array_reduce($transactions, function ($carry, TransactionDetails $item) {
            $carry += $item->getGrossValue();

            return $carry;
        }, 0);
    }
}
