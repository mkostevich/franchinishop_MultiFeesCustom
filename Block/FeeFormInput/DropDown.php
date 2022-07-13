<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Block\FeeFormInput;

class DropDown extends \MageWorx\MultiFees\Block\FeeFormInput\DropDown
{
    /**
     * Render form input component for the fee
     *
     * @return array
     */
    public function render(): array
    {
        $isApplyOnClick                  = $this->helper->isApplyOnClick();
        $component                       = [];
        $component['component']          = $this->getSelectComponent();
        $component['config']             = [
            'customScope' => $this->scope,
            'template'    => 'MageWorx_MultiFees/form/field',
            'elementTmpl' => 'ui/form/element/select'
        ];
        $component['dataScope']          = $this->getDataScope();
        $component['label']              = $this->fee->getTitle();
        $component['provider']           = 'checkoutProvider';
        $component['visible']            = true;
        $component['validation']         = [];
        $component['applyOnClick']       = $isApplyOnClick;
        $component['isVisibleInputType'] = static::VISIBLE_TYPE;
        $component['name']               = $this->getFeeName();

        $options = [];

        if ($this->fee->getRequired()) {
            $component['validation']['required-entry'] = 'true';
        } else {
            $options[] =
                [
                    'label' => __('None'),
                    'value' => 0
                ];
        }

        foreach ($this->fee->getOptions() as $option) {
            if (!empty($this->details[$this->fee->getId()]['options'][$option->getId()])) {
                $component['value'] = $option->getId();
            }

            $optionTitle = $option->getTitle();
            $optionPrice = $this->helperPrice->getOptionFormatPrice($option, $this->fee);
            $optionLabel = __('%1 per %2', $optionTitle, $optionPrice);

            $options[] =
                [
                    'label' => $optionLabel,
                    'value' => $option->getId()
                ];

            if ($option->getIsDefault()) {
                $defaultOption = $option;
            }
        }

        if (empty($component['value'])) {
            if (isset($defaultOption)) {
                $component['value'] = $defaultOption->getId();
            } else {
                $component['value'] = !empty($options[0]['value']) ? $options[0]['value'] : null;
            }
        }

        $component['notice']    = $this->fee->getDescription();
        $component['options']   = $options;
        $component['feeId']     = $this->fee->getId();
        $component['sortOrder'] = (int)$this->fee->getSortOrder();
        $component['feeType']   = $this->fee->getType();

        return $component;
    }
}
