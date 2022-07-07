<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Cart;
use MageWorx\MultiFees\Api\Data\FeeInterface;
use MageWorx\MultiFees\Api\QuoteProductFeeManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class AddProductFeesObserver implements ObserverInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var QuoteProductFeeManagerInterface
     */
    protected $quoteFeeManager;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param Registry $registry
     * @param Cart $cart
     * @param QuoteProductFeeManagerInterface $quoteFeeManager
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Registry $registry,
        Cart $cart,
        QuoteProductFeeManagerInterface $quoteFeeManager,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->registry        = $registry;
        $this->cart            = $cart;
        $this->quoteFeeManager = $quoteFeeManager;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param Observer $observer
     * @throws \MageWorx\MultiFees\Exception\RefactoringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $request  = $observer->getEvent()->getRequest();
        $feesPost = $request->getPost('fee');

        if ($feesPost && is_array($feesPost)) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $this->registry->registry('mageworx_multifeescustom_quote_item');

            if (!$quoteItem || !$quoteItem->getId()) {
                return;
            }

            $cartQuote = $this->cart->getQuote();

            if (!$cartQuote->getItemsCount()) {
                return;
            }

            $this->quoteFeeManager->addFeesToQuote(
                $this->prepareFeePost($feesPost, (int)$quoteItem->getId()),
                $cartQuote,
                true,
                FeeInterface::PRODUCT_TYPE,
                $this->quoteFeeManager->getAddressFromQuote($cartQuote)->getId()
            );
            $cartQuote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($cartQuote);
        }
    }

    /**
     * @param array $feesPost
     * @param int $itemId
     * @return array
     */
    protected function prepareFeePost(array $feesPost, int $itemId): array
    {
        $newFeePost = [];

        foreach ($feesPost as $feeId => $feeData) {
            if (empty($feeData['options'])) {
                continue;
            }

            $newOptions = [];
            foreach ($feeData['options'] as $option) {
                $newOptions[$option] = [];
            }

            $data = [
                'options' => $newOptions,
                'type'    => FeeInterface::PRODUCT_TYPE
            ];

            if (!empty($feeData['message'])) {
                $data['message'] = $feeData['message'];
            }
            if (!empty($feeData['date'])) {
                $data['date'] = $feeData['date'];
            }

            $newFeePost[$feeId][$itemId] = $data;
        }

        return $newFeePost;
    }
}
