<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Model\Resolver\Product;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Api\FeeCollectionManagerInterface;
use MageWorx\MultiFees\Api\QuoteProductFeeManagerInterface;
use MageWorx\MultiFees\Helper\Data;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ProductFeeCollection;

class AllFees extends \MageWorx\MultiFeesGraphQl\Model\Resolver\Product\ProductHiddenFees
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param QuoteItemFactory $quoteItemFactory
     * @param FeeCollectionManagerInterface $feeCollectionManager
     * @param QuoteProductFeeManagerInterface $quoteFeeManager
     * @param HelperPrice $helperPrice
     * @param Data $helperData
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        QuoteItemFactory $quoteItemFactory,
        FeeCollectionManagerInterface $feeCollectionManager,
        QuoteProductFeeManagerInterface $quoteFeeManager,
        HelperPrice $helperPrice,
        Data $helperData,
        PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct($quoteItemFactory, $feeCollectionManager, $quoteFeeManager, $helperPrice, $helperData);
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return ProductFeeCollection
     * @throws LocalizedException
     */
    protected function getFeeCollection(): ProductFeeCollection
    {
        return $this->feeCollectionManager->getProductFeeCollection(
            false,
            false,
            FeeCollectionManagerInterface::HIDDEN_MODE_ALL
        );
    }

    /**
     * @param FeeInterface $fee
     * @return array
     * @throws LocalizedException
     */
    protected function getPreparedFeeData(FeeInterface $fee): array
    {
        $data               = parent::getPreparedFeeData($fee);
        $data['min_amount'] = $fee->getMinAmount() ? $this->getMinAmountData((float)$fee->getMinAmount()) : null;

        $data['is_enabled_customer_message'] = (bool)$fee->getEnableCustomerMessage();
        $data['customer_message_title']      = $fee->getCustomerMessageTitle();
        $data['is_enabled_date_field']       = (bool)$fee->getEnableDateField();
        $data['date_field_title']            = $fee->getDateFieldTitle();

        return $data;
    }

    /**
     * @param FeeInterface $fee
     * @return array
     * @throws LocalizedException
     */
    protected function getPreparedFeeOptions(FeeInterface $fee): array
    {
        $options = [];

        foreach ($fee->getOptions() as $option) {
            $title     = $option->getTitle();
            $options[] = [
                'id'          => (int)$option->getId(),
                'field_label' => __('%1 per %2', $title, $this->helperPrice->getOptionFormatPrice($option, $fee)),
                'is_default'  => (bool)$option->getIsDefault(),
                'position'    => (int)$option->getPosition(),
                'title'       => $title,
                'price_type'  => $option->getPriceType(),
                'price'       => (float)$option->getPrice()
            ];
        }

        return $options;
    }

    /**
     * @param float $amount
     * @return array
     * @throws LocalizedException
     */
    protected function getMinAmountData(float $amount): array
    {
        $quote = $this->feeCollectionManager->getQuote();
        $store = $quote->getStore();
        $price = $this->priceCurrency->convert($amount, $store); // base price - to store price

        return [
            'value'    => $price,
            'currency' => $store->getCurrentCurrencyCode()
        ];
    }
}
