<?php

/**
 * HiPay Fullservice SDK Magento 1
 *
 * 2018 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.tpp@hipay.com>
 * @copyright 2018 HiPay
 * @license   https://github.com/hipay/hipay-fullservice-sdk-magento1/blob/master/LICENSE.md
 */

/**
 *
 *
 * @author      HiPay <support.tpp@hipay.com>
 * @copyright   Copyright (c) 2018 - HiPay
 * @license     https://github.com/hipay/hipay-fullservice-sdk-magento1/blob/master/LICENSE.md
 * @link    https://github.com/hipay/hipay-fullservice-sdk-magento1
 */
class Allopass_Hipay_Block_Form_Cc extends Allopass_Hipay_Block_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('hipay/form/cc.phtml');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $fingerprint = $this->getLayout()->createBlock('hipay/checkout_fingerprint', 'hipay.checkout.fingerprint');
        $this->setChild('hipay_fingerprint', $fingerprint);

        $oneclick = $this->getLayout()->createBlock('hipay/checkout_oneclick', 'hipay.checkout.oneclick');
        $this->setChild('hipay_oneclick', $oneclick);

        $splitpayment = $this->getLayout()->createBlock('hipay/checkout_splitpayment', 'hipay.checkout.splitpayment');
        $this->setChild('hipay_splitpayment', $splitpayment);

        return $this;
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);


                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }

                $ordered = array();
                foreach ($availableTypes as $key) {
                    if (array_key_exists($key, $types)) {
                        $ordered[$key] = $types[$key];
                        unset($types[$key]);
                    }
                }

                return $ordered;
            }
        }

        return $types;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if ($months === null) {
            $months["0"] = $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }

        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if ($years === null) {
            $years = $this->_getConfig()->getYears();
            $years = array(0 => $this->__('Year')) + $years;
            $this->setData('cc_years', $years);
        }

        return $years;
    }

    /**
     * Retrive has verification configuration
     *
     * @return boolean
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if ($configData === null) {
                return true;
            }

            return (bool)$configData;
        }

        return true;
    }

    /**
     * Whether switch/solo card type available
     *
     * @return bool
     */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', $this->getMethod()->getConfigData('cctypes'));
        $ssPresenations = array_intersect(array('SS', 'SM', 'SO'), $availableTypes);
        if ($availableTypes && !empty($ssPresenations)) {
            return true;
        }

        return false;
    }

    /*
    * solo/switch card start year
    * @return array
    */
    public function getSsStartYears()
    {
        $years = array();
        $first = Mage::getSingleton('core/date')->date('Y');

        for ($index = 5; $index >= 0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }

        return array(0 => $this->__('Year')) + $years;
    }


}
