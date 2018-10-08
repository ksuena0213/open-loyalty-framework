<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Transaction\Domain\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Core\Domain\Model\SKU;
use OpenLoyalty\Component\Core\Domain\ReadModel\Versionable;
use OpenLoyalty\Component\Core\Domain\ReadModel\VersionableReadModel;
use OpenLoyalty\Component\Transaction\Domain\CustomerId;
use OpenLoyalty\Component\Transaction\Domain\Model\CustomerBasicData;
use OpenLoyalty\Component\Transaction\Domain\Model\Item;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Transaction\Domain\PosId;
use OpenLoyalty\Component\Transaction\Domain\Transaction;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class TransactionDetails.
 */
class TransactionDetails implements SerializableReadModel, VersionableReadModel
{
    use Versionable;

    /**
     * @var TransactionId
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $documentNumber;

    /**
     * @var \DateTime
     */
    protected $purchaseDate;

    /**
     * @var string
     */
    protected $purchasePlace;

    /**
     * @var string
     */
    protected $documentType;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var CustomerBasicData
     */
    protected $customerData;

    /**
     * @var Item[]
     */
    protected $items;

    /**
     * @var Label[]
     */
    protected $labels;

    /**
     * @var PosId
     */
    protected $posId;

    /**
     * @var array
     */
    protected $excludedDeliverySKUs;

    /**
     * @var array
     */
    protected $excludedLevelSKUs;

    /**
     * @var array
     */
    protected $excludedLevelCategories;

    protected $revisedDocument;

    /**
     * TransactionDetails constructor.
     *
     * @param TransactionId $transactionId
     */
    public function __construct(TransactionId $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->transactionId->__toString();
    }

    /**
     * @param array $data
     *
     * @return TransactionDetails
     */
    public static function deserialize(array $data)
    {
        $items = [];
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $items[] = Item::deserialize($item);
            }
        }

        $labels = [];
        if (isset($data['labels'])) {
            foreach ($data['labels'] as $label) {
                $labels[] = Label::deserialize($label);
            }
        }

        if (is_numeric($data['purchaseDate'])) {
            $tmp = new \DateTime();
            $tmp->setTimestamp($data['purchaseDate']);
            $data['purchaseDate'] = $tmp;
        }
        $customerData = $data['customerData'];

        $transaction = new self(new TransactionId($data['transactionId']));
        $transaction->labels = $labels;

        $transaction->customerData = CustomerBasicData::deserialize($customerData);
        $transaction->items = $items;
        if (!empty($data['customerId'])) {
            $transaction->customerId = new CustomerId($data['customerId']);
        }
        $transaction->documentNumber = $data['documentNumber'];
        $transaction->documentType = isset($data['documentType']) ? $data['documentType'] : Transaction::TYPE_SELL;
        $transaction->purchasePlace = $data['purchasePlace'];
        $transaction->purchaseDate = $data['purchaseDate'];
        $transaction->revisedDocument = isset($data['revisedDocument']) ? $data['revisedDocument'] : null;
        if (isset($data['excludedDeliverySKUs'])) {
            $transaction->excludedDeliverySKUs = json_decode($data['excludedDeliverySKUs'], true);
        }
        if (isset($data['excludedLevelSKUs'])) {
            $transaction->excludedLevelSKUs = json_decode($data['excludedLevelSKUs'], true);
        }
        if (isset($data['excludedLevelCategories'])) {
            $transaction->excludedLevelCategories = json_decode($data['excludedLevelCategories'], true);
        }

        if (isset($data['posId'])) {
            $transaction->posId = new PosId($data['posId']);
        }

        return $transaction;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->serialize();
        }
        $labels = [];
        foreach ($this->labels as $label) {
            $labels[] = $label->serialize();
        }

        return [
            'customerId' => $this->customerId ? $this->customerId->__toString() : null,
            'transactionId' => $this->transactionId->__toString(),
            'documentType' => $this->documentType,
            'documentNumber' => $this->documentNumber,
            'documentNumberRaw' => $this->documentNumber,
            'purchaseDate' => $this->purchaseDate->getTimestamp(),
            'purchasePlace' => $this->purchasePlace,
            'customerData' => $this->customerData->serialize(),
            'items' => $items,
            'posId' => $this->posId ? $this->posId->__toString() : null,
            'excludedDeliverySKUs' => $this->excludedDeliverySKUs ? json_encode($this->excludedDeliverySKUs) : null,
            'excludedLevelSKUs' => $this->excludedLevelSKUs ? json_encode($this->excludedLevelSKUs) : null,
            'excludedLevelCategories' => $this->excludedLevelCategories ? json_encode(
                $this->excludedLevelCategories
            ) : null,
            'revisedDocument' => $this->revisedDocument,
            'labels' => $labels,
        ];
    }

    /**
     * @return TransactionId
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * @param string $documentNumber
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;
    }

    /**
     * @return \DateTime
     */
    public function getPurchaseDate()
    {
        return $this->purchaseDate;
    }

    /**
     * @param \DateTime $purchaseDate
     */
    public function setPurchaseDate($purchaseDate)
    {
        $this->purchaseDate = $purchaseDate;
    }

    /**
     * @return string
     */
    public function getPurchasePlace()
    {
        return $this->purchasePlace;
    }

    /**
     * @param string $purchasePlace
     */
    public function setPurchasePlace($purchasePlace)
    {
        $this->purchasePlace = $purchasePlace;
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param CustomerId $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return CustomerBasicData
     */
    public function getCustomerData()
    {
        return $this->customerData;
    }

    /**
     * @param CustomerBasicData $customerData
     */
    public function setCustomerData($customerData)
    {
        $this->customerData = $customerData;
    }

    /**
     * @return \OpenLoyalty\Component\Transaction\Domain\Model\Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param \OpenLoyalty\Component\Transaction\Domain\Model\Item[] $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * @return PosId
     */
    public function getPosId()
    {
        return $this->posId;
    }

    /**
     * @param PosId $posId
     */
    public function setPosId($posId)
    {
        $this->posId = $posId;
    }

    /**
     * @return array
     */
    public function getExcludedDeliverySKUs()
    {
        return $this->excludedDeliverySKUs;
    }

    /**
     * @param array $excludedDeliverySKUs
     */
    public function setExcludedDeliverySKUs($excludedDeliverySKUs)
    {
        $this->excludedDeliverySKUs = $excludedDeliverySKUs;
    }

    /**
     * @return array
     */
    public function getExcludedLevelSKUs()
    {
        return $this->excludedLevelSKUs;
    }

    /**
     * @param array $excludedLevelSKUs
     */
    public function setExcludedLevelSKUs($excludedLevelSKUs)
    {
        $this->excludedLevelSKUs = $excludedLevelSKUs;
    }

    /**
     * @return array
     */
    public function getExcludedLevelCategories()
    {
        return $this->excludedLevelCategories;
    }

    /**
     * @param array $excludedLevelCategories
     */
    public function setExcludedLevelCategories($excludedLevelCategories)
    {
        $this->excludedLevelCategories = $excludedLevelCategories;
    }

    public function getAmountExcludedForLevel()
    {
        if (!$this->excludedLevelSKUs) {
            $excludedSKUs = [];
        } else {
            $excludedSKUs = array_map(
                function ($obj) {
                    if ($obj instanceof SKU) {
                        return $obj->getCode();
                    }

                    return $obj;
                },
                $this->getExcludedLevelSKUs()
            );
        }

        if (!$this->excludedLevelCategories) {
            $excludedCategories = [];
        } else {
            $excludedCategories = array_map(
                function ($obj) {
                    return $obj;
                },
                $this->getExcludedLevelCategories()
            );
        }

        $amountSKUs = array_reduce(
            $this->items,
            function ($carry, Item $item) use ($excludedSKUs) {
                if (!in_array($item->getSku()->getCode(), $excludedSKUs)) {
                    return $carry;
                }
                $carry += $item->getGrossValue();

                return $carry;
            },
            0
        );

        $amountCategories = array_reduce(
            $this->items,
            function ($carry, Item $item) use ($excludedCategories) {
                if (!in_array($item->getCategory(), $excludedCategories)) {
                    return $carry;
                }
                $carry += $item->getGrossValue();

                return $carry;
            },
            0
        );

        return $amountSKUs + $amountCategories;
    }

    /**
     * @param array $excludeSKUs
     * @param array $excludeLabels
     * @param array $includeLabels
     * @param bool  $excludeDelivery
     *
     * @return Item[]
     */
    public function getFilteredItems(array $excludeSKUs = [], array $excludeLabels = [], array $includeLabels, $excludeDelivery = false)
    {
        //TODO: Refactor: should be one type of parameter
        /** @var string[] $excludeSKUs */
        $excludeSKUs = array_map(
            function ($sku) {
                return $sku instanceof SKU ? $sku->getCode() : $sku;
            },
            $excludeSKUs
        );

        //TODO: Refactor: should be one type of parameter
        /** @var Label[] $excludeLabels */
        $excludeLabels = array_map(
            function ($label) {
                return $label instanceof Label ? $label : new Label($label['key'], $label['value']);
            },
            $excludeLabels
        );
        /** @var Label[] $includeLabels */
        $includeLabels = array_map(
            function ($label) {
                return $label instanceof Label ? $label : new Label($label['key'], $label['value']);
            },
            $includeLabels
        );

        if ($excludeDelivery && !empty($this->excludedDeliverySKUs)) {
            $excludeSKUs = array_merge($excludeSKUs, $this->excludedDeliverySKUs);
        }

        return array_filter(
            $this->items,
            function (Item $item) use ($excludeSKUs, $excludeLabels, $includeLabels) {
                // filter items by SKU
                if (in_array($item->getSku()->getCode(), $excludeSKUs)) {
                    return false;
                }

                if (count($excludeLabels) > 0) {
                    // filter items by Label
                    foreach ($excludeLabels as $excludeLabel) {
                        foreach ($item->getLabels() as $label) {
                            if ($label->getKey() == $excludeLabel->getKey()
                                && $label->getValue() == $excludeLabel->getValue()
                            ) {
                                return false;
                            }
                        }
                    }
                } elseif (count($includeLabels) > 0) {
                    // filter items by Label
                    $productHasLabel = false;
                    foreach ($includeLabels as $includeLabel) {
                        foreach ($item->getLabels() as $label) {
                            if ($label->getKey() === $includeLabel->getKey()
                                && $label->getValue() === $includeLabel->getValue()
                            ) {
                                $productHasLabel = true;
                                break;
                            }
                        }
                    }
                    if (!$productHasLabel) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @param array $excludeAdditionalSKUs
     * @param array $excludeLabels
     * @param array $includedLabels
     * @param bool  $excludeDelivery
     *
     * @return float
     */
    public function getGrossValue(
        array $excludeAdditionalSKUs = [],
        array $excludeLabels = [],
        array $includedLabels = [],
        $excludeDelivery = false
    ) {
        $filteredItems = $this->getFilteredItems($excludeAdditionalSKUs, $excludeLabels, $includedLabels, $excludeDelivery);

        return array_reduce(
            $filteredItems,
            function ($carry, Item $item) {
                return $carry + $item->getGrossValue();
            },
            0
        );
    }

    /**
     * @param array $excludeAdditionalSKUs
     * @param array $excludeLabels
     *
     * @return float
     */
    public function getGrossValueWithoutDeliveryCosts(array $excludeAdditionalSKUs = [], array $excludeLabels = [], array $includedLabels = [])
    {
        return $this->getGrossValue($excludeAdditionalSKUs, $excludeLabels, $includedLabels, true);
    }

    /**
     * @return mixed
     */
    public function getRevisedDocument()
    {
        return $this->revisedDocument;
    }

    /**
     * @param mixed $revisedDocument
     */
    public function setRevisedDocument($revisedDocument)
    {
        $this->revisedDocument = $revisedDocument;
    }

    /**
     * @return Label[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param Label[] $labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @param array $labels
     */
    public function appendLabels(array $labels)
    {
        $this->labels = array_merge($this->labels, $labels);
    }
}
