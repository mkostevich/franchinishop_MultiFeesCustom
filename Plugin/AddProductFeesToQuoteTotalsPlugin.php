<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Escaper;
use Magento\Quote\Api\Data\TotalsInterface as QuoteTotalsInterface;
use MageWorx\MultiFees\Block\Cart\ProductFeeInfo;
use Magento\Quote\Model\Cart\CartTotalRepository;

class AddProductFeesToQuoteTotalsPlugin
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \MageWorx\MultiFees\Helper\Data
     */
    protected $helperData;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var ProductFeeInfo
     */
    protected $blockProductFeeInfo;

    /**
     * @param CheckoutSession $checkoutSession
     * @param \MageWorx\MultiFees\Helper\Data $helperData
     * @param Escaper $escaper
     * @param ProductFeeInfo $blockProductFeeInfo
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \MageWorx\MultiFees\Helper\Data $helperData,
        Escaper $escaper,
        ProductFeeInfo $blockProductFeeInfo
    ) {
        $this->checkoutSession     = $checkoutSession;
        $this->helperData          = $helperData;
        $this->escaper             = $escaper;
        $this->blockProductFeeInfo = $blockProductFeeInfo;
    }

    /**
     * @param CartTotalRepository $subject
     * @param QuoteTotalsInterface $result
     * @return QuoteTotalsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGet(CartTotalRepository $subject, $result): QuoteTotalsInterface
    {
        if ($result->getItems() !== null) {
            foreach ($result->getItems() as $item) {
                $newOptions = $this->getFormattedOptionValue((int)$item->getItemId());

                if (empty($newOptions)) {
                    continue;
                }

                $options = $this->helperData->unserializeValue($item->getOptions());
                $options = array_merge_recursive($options, $newOptions);

                $item->setOptions($this->helperData->serializeValue($options));
            }
        }

        return $result;
    }

    /**
     * @param int $itemId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getFormattedOptionValue(int $itemId): array
    {
        $optionsData = [];
        $feeDetails  = $this->getFeeDetailsByItemId($itemId);

        foreach ($feeDetails as $fee) {
            $optionsData[] = $this->getFeeAsOption($fee);
        }

        return $optionsData;
    }

    /**
     * @param int $itemId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFeeDetailsByItemId(int $itemId): array
    {
        $fees     = [];
        $quote    = $this->checkoutSession->getQuote();
        $fullInfo = $quote->getMageworxProductFeeDetails();

        if (!$fullInfo) {
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
            $fullInfo = $address->getMageworxProductFeeDetails();
        }

        if ($fullInfo) {
            $fullInfo = $this->helperData->unserializeValue($fullInfo);
            foreach ($fullInfo as $feeId => $quoteItemData) {
                foreach ($quoteItemData as $quoteItemId => $fee) {
                    if (isset($fee['type']) && $itemId == $quoteItemId) {
                        $fees[$feeId] = $fee;
                    }
                }
            }
        }

        return $fees;
    }

    /**
     * @param array $fee
     * @return array
     */
    protected function getFeeAsOption(array $fee): array
    {
        $optionValue = [];
        foreach ($fee['options'] as $option) {
            $optionValue[] = $this->escaper->escapeHtml($option['title'])
                . $this->blockProductFeeInfo->getOptionPriceHtml($option);
        }

        if (isset($fee['date']) && $fee['date']) {
            $optionValue[] = $this->escaper->escapeHtml(trim($fee['date_title'], ':'))
                . ': <i>' . $this->escaper->escapeHtml($fee['date']) . '</i><br/>';
        }
        if (isset($fee['message']) && $fee['message']) {
            $optionValue[] = $this->escaper->escapeHtml(trim($fee['message_title'], ':'))
                . ': <i>' . $this->escaper->escapeHtml($fee['message']) . '</i><br/>';
        }

        return [
            'value' => nl2br(implode('', $optionValue)),
            'label' => $this->escaper->escapeHtml($fee['title'])
        ];
    }
}
