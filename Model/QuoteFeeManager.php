<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Model;

use MageWorx\MultiFees\Model\ResourceModel\Fee\AbstractCollection;

class QuoteFeeManager extends \MageWorx\MultiFees\Model\QuoteFeeManager
{
    /**
     * @param AbstractCollection $feeCollection
     * @return array
     */
    protected function prepareFeesArray(AbstractCollection $feeCollection): array
    {
        $feeArray = [];
        foreach ($feeCollection as $fee) {
            $feeArray[$fee->getId()] = $fee->getData();
            $options                 = [];
            foreach ($fee->getOptions() as $option) {
                $optionFormatPrice = $this->helperPrice->getOptionFormatPrice($option, $fee);
                $optionFieldLabel  = __('%1 per %2', $option->getTitle(), $optionFormatPrice);
                $option->setData('field_label', $optionFieldLabel);
                $options[$option->getId()] = $option->getData();
            }
            $feeArray[$fee->getId()]['options'] = $options;
        }

        return $feeArray;
    }
}
