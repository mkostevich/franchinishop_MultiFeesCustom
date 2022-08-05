<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Block\LayoutProcessor;

use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Api\FeeCollectionManagerInterface;
use MageWorx\MultiFees\Api\QuoteFeeManagerInterface;
use MageWorx\MultiFees\Api\QuoteProductFeeManagerInterface;
use MageWorx\MultiFees\Helper\Data as Helper;
use MageWorx\MultiFees\Helper\Price as HelperPrice;
use MageWorx\MultiFees\Model\FeeCollectionValidationManager;

class ProductLayoutProcessor extends \MageWorx\MultiFees\Block\LayoutProcessor\ProductLayoutProcessor
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $merger
     * @param Helper $helper
     * @param QuoteFeeManagerInterface $quoteFeeManager
     * @param QuoteProductFeeManagerInterface $quoteProductFeeManager
     * @param HelperPrice $helperPrice
     * @param \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param \Psr\Log\LoggerInterface $logger
     * @param FeeCollectionValidationManager $feeCollectionValidationManager
     * @param FeeCollectionManagerInterface $feeCollectionManager
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Checkout\Block\Checkout\AttributeMerger $merger,
        Helper $helper,
        QuoteFeeManagerInterface $quoteFeeManager,
        QuoteProductFeeManagerInterface $quoteProductFeeManager,
        HelperPrice $helperPrice,
        \MageWorx\MultiFees\Block\FeeFormInputPlant $feeFormInputRendererFactory,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger,
        FeeCollectionValidationManager $feeCollectionValidationManager,
        FeeCollectionManagerInterface $feeCollectionManager,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct(
            $merger,
            $helper,
            $quoteFeeManager,
            $quoteProductFeeManager,
            $helperPrice,
            $feeFormInputRendererFactory,
            $escaper,
            $logger,
            $feeCollectionValidationManager,
            $feeCollectionManager
        );
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @param array $jsLayout
     * @return array
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($jsLayout)
    {
        if (isset(
            $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']
            ['children']['cart_items']['children']['mw-multifeescustom-product-fee-containers']
        )) {
            $containers = &$jsLayout['components']['checkout']['children']['sidebar']['children']['summary']
            ['children']['cart_items']['children']['mw-multifeescustom-product-fee-containers']['children'];

            $isApplyOnClick = $this->helper->isApplyOnClick();
            $quote          = $this->helper->getQuote();
            $items          = $quote->getAllItems();

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($items as $item) {
                $this->helper->setCurrentItem($item);

                try {
                    $productFeeComponents = $this->getFeeComponents();
                    $this->coreRegistry->unregister('current_item');

                    if (empty($productFeeComponents)) {
                        continue;
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->logger->critical($e->getLogMessage());
                    $productFeeComponents = [];
                }

                $itemId                     = $item->getId();
                $containerName              = 'mageworx-product-fee-form-container' . $itemId;
                $containers[$containerName] = $containers['mageworx-product-fee-form-container'];

                $containers[$containerName]['itemId']       = $itemId;
                $containers[$containerName]['applyOnClick'] = $isApplyOnClick;
                $containers[$containerName]['typeId']       = FeeInterface::PRODUCT_TYPE;
                $containers[$containerName]['displayArea']  = $containerName;
                $containers[$containerName]['feeCount']     = count($productFeeComponents);

                $fieldSetPointer = &$containers[$containerName]['children']['mageworx-fee-form-fieldset']['children'];

                foreach ($productFeeComponents as $component) {
                    if ($component['component'] === 'MageWorx_MultiFees/js/form/element/product-checkbox-set') {
                        $component['config']['template']    = 'MageWorx_MultiFeesCustom/form/field';
                        $component['config']['elementTmpl'] = 'MageWorx_MultiFeesCustom/form/element/checkbox-set';
                    }
                    $fieldSetPointer[] = $component;
                }
            }
        }

        return $jsLayout;
    }
}
