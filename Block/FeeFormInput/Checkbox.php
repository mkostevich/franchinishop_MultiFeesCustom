<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\MultiFeesCustom\Block\FeeFormInput;

class Checkbox extends \MageWorx\MultiFees\Block\FeeFormInput\Checkbox
{
    /**
     * Render form input component for the fee
     *
     * @return array
     */
    public function render(): array
    {
        $isApplyOnClick                  = $this->helper->isApplyOnClick();
        $scope                           = $this->scope;
        $component                       = [];
        $component['component']          = $this->getCheckboxComponent();
        $component['config']             = [
            'customScope' => $scope,
            'template'    => 'MageWorx_MultiFees/form/field',
            'elementTmpl' => 'MageWorx_MultiFees/form/element/checkbox-set'
        ];
        $component['dataScope']          = $this->getDataScope();
        $component['label']              = $this->fee->getTitle();
        $component['provider']           = 'checkoutProvider';
        $component['visible']            = true;
        $component['validation']         = [];
        $component['applyOnClick']       = $isApplyOnClick;
        $component['isVisibleInputType'] = static::VISIBLE_TYPE;
        $component['multiple']           = true;
        $component['name']               = $this->getFeeName();
        $selectedOptions                 = [];

        $options = [];

        if ($this->fee->getRequired()) {
            $component['validation']['required-entry'] = 'true';
        }

        $defaultSelectedOptions = [];
        foreach ($this->fee->getOptions() as $option) {
            if (!empty($this->details[$this->fee->getId()]['options'][$option->getId()])) {
                $selectedOptions[] = $option->getId();
            }

            $optionTitle = __(
                '%1 per %2',
                $option->getTitle(),
                $this->helperPrice->getOptionFormatPrice($option, $this->fee)
            );
            $options[]   =
                [
                    'label' => $optionTitle,
                    'value' => $option->getId(),
                ];

            if ($option->getIsDefault()) {
                $defaultSelectedOptions[] = $option->getId();
            }
        }

        if (empty($selectedOptions)) {
            $selectedOptions = $defaultSelectedOptions;
        }

        $component['notice']    = $this->fee->getDescription();
        $component['options']   = $options;
        $component['feeId']     = $this->fee->getId();
        $component['sortOrder'] = (int)$this->fee->getSortOrder();
        $component['value']     = $selectedOptions;
        $component['feeType']   = $this->fee->getType();

        return $component;
    }
}
