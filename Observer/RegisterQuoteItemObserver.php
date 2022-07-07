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
use Magento\Framework\App\RequestInterface;

class RegisterQuoteItemObserver implements ObserverInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Registry $registry
     * @param RequestInterface $request
     */
    public function __construct(Registry $registry, RequestInterface $request)
    {
        $this->registry = $registry;
        $this->request  = $request;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->request->getPost('fee')) {
            $this->registry->register('mageworx_multifeescustom_quote_item', $observer->getEvent()->getQuoteItem());
        }
    }
}
