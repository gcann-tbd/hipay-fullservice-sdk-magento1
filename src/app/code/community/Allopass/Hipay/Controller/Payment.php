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
class Allopass_Hipay_Controller_Payment extends Mage_Core_Controller_Front_Action
{
    /**
     *
     * @var Mage_Sales_Model_Order $order
     */
    protected $_order = null;


    /**
     * @return Mage_Core_Controller_Front_Action
     */
    public function preDispatch()
    {
        parent::preDispatch();
    }


    /**
     *
     * @return Allopass_Hipay_Model_Method_Abstract $methodInstance
     */
    protected function _getMethodInstance()
    {
        Mage::throwException("Method: '" . __METHOD__ . "' must be implemented!");
    }

    public function sendRequestAction()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $amount = $order->getBaseTotalDue();

        $methodInstance = $this->_getMethodInstance();
        $useOrderCurrency = Mage::getStoreConfig('hipay/hipay_api/currency_transaction', Mage::app()->getStore());

        if ($useOrderCurrency) {
            $amount = $order->getTotalDue();
        }

        try {
            $redirectUrl = $methodInstance->place($payment, $amount);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getCheckout()->addError($e->getMessage());
            $this->_redirect('checkout/cart');
            return $this;
        }

        $this->_redirectUrl($redirectUrl);

        return $this;
    }


    public function acceptAction()
    {
        if (($profileIds = Mage::getSingleton('checkout/session')->getLastRecurringProfileIds())) {
            if (is_array($profileIds)) {
                /* @var $gatewayResponse Allopass_Hipay_Model_Api_Response_Gateway */
                $gatewayResponse = Mage::getSingleton('hipay/api_response_gateway', $this->getRequest()->getParams());
                $collection = Mage::getModel('sales/recurring_profile')->getCollection()
                    ->addFieldToFilter('profile_id', array('in' => $profileIds));
                foreach ($collection as $profile) {
                    $additionalInfo = array();
                    $additionalInfo['ccType'] = $gatewayResponse->getBrand();
                    $additionalInfo['ccExpMonth'] = $gatewayResponse->getCardExpiryMonth();
                    $additionalInfo['ccExpYear'] = $gatewayResponse->getCardExpiryYear();
                    $additionalInfo['token'] = $gatewayResponse->getToken();
                    $additionalInfo['transaction_id'] = $gatewayResponse->getTransactionReference();
                    $profile->setAdditionalInfo($additionalInfo);
                    $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);

                    $profile->save();
                }
            }


            $session = Mage::getSingleton('checkout/session');
            if (!$session->getLastSuccessQuoteId()) {
                $session->setLastSuccessQuoteId($this->getOrder()->getIncrementId());
                $session->setLastQuoteId($this->getOrder()->getId());
            }
        }

        $this->processResponse();
        $urlRedirect = Mage::helper('hipay')->getCheckoutSuccessPage($this->getOrder()->getPayment());

        if (preg_match('/http/', $urlRedirect)) {
            $this->_redirectUrl($urlRedirect);
        } else {
            $this->_redirect($urlRedirect);
        }

        return $this;
    }

    public function pendingAction()
    {
        $this->processResponse();

        $this->_redirect($this->_getMethodInstance()->getConfigData('pending_redirect_page'));

        return $this;
    }

    public function declineAction()
    {
        $lastOrderId = $this->getOrder()->getIncrementId();

        Mage::getSingleton('checkout/session')->setLastQuoteId($lastOrderId);
        Mage::getSingleton('checkout/session')->setLastOrderId($lastOrderId);

        $this->processResponse();

        // Translate with Helper
        Mage::getSingleton('checkout/session')->addError(
            Mage::helper('hipay')->__("Your payment is declined. Please retry checkout with another payment card.")
        );

        $this->_redirect(Mage::helper('hipay')->getCheckoutFailurePage($this->getOrder()->getPayment()));

        return $this;
    }


    public function exceptionAction()
    {
        $lastOrderId = $this->getOrder()->getIncrementId();

        Mage::getSingleton('checkout/session')->setLastQuoteId($lastOrderId);
        Mage::getSingleton('checkout/session')->setLastOrderId($lastOrderId);

        // Translate with Helper
        Mage::getSingleton('checkout/session')->addError(
            Mage::helper('hipay')->__("An exception has occured. Please retry checkout.")
        );

        $this->_redirect('checkout/cart');
        return $this;
    }


    public function cancelAction()
    {
        $this->processResponse();
        $this->_redirect('checkout/cart');
        return $this;
    }

    protected function processResponse()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        /* @var $gatewayResponse Allopass_Hipay_Model_Api_Response_Gateway */
        $gatewayResponse = Mage::getSingleton('hipay/api_response_gateway', $this->getRequest()->getParams());

        if (!$payment && $gatewayResponse->getData('order')) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($gatewayResponse->getData('order'));
            $this->_order = $order;
            $payment = $order->getPayment();

            $session = Mage::getSingleton('checkout/session');
            if (!$session->getLastOrderId()) {
                $session->setLastOrderId($this->getOrder()->getIncrementId());
            }

            if (!$session->getLastSuccessQuoteId()) {
                $session->setLastSuccessQuoteId($this->getOrder()->getIncrementId());
                $session->setLastQuoteId($this->getOrder()->getId());
            }
        } else if ($payment) {
            $order = $payment->getOrder();
        }

        return $this->_getMethodInstance()->processResponseToRedirect(
            $gatewayResponse,
            $payment,
            $order->getBaseTotalDue()
        );
    }


    /**
     *
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        if (is_null($this->_order)) {
            if (($profileIds = $this->getCheckout()->getLastRecurringProfileIds())) {
                if (is_array($profileIds)) {
                    foreach ($profileIds as $profileId) {
                        /* @var $profile Mage_Sales_Model_Recurring_Profile */
                        $profile = Mage::getModel('sales/recurring_profile')->load($profileId);
                        /* @var $_helperRecurring Allopass_Hipayrecurring_Helper_Data */
                        $_helperRecurring = Mage::helper('hipayrecurring');

                        if ($_helperRecurring->isInitialProfileOrder($profile)) {
                            $this->_order = $_helperRecurring->createOrderFromProfile($profile);
                        } else {
                            $orderId = current($profile->getChildOrderIds());
                            $this->_order = Mage::getModel('sales/order')->load($orderId);

                            $additionalInfo = $profile->getAdditionalInfo();

                            $this->_order->getPayment()->setCcType(
                                isset($additionalInfo['ccType']) ? $additionalInfo['ccType'] : ""
                            );
                            $this->_order->getPayment()->setCcExpMonth(
                                isset($additionalInfo['ccExpMonth']) ? $additionalInfo['ccExpMonth'] : ""
                            );
                            $this->_order->getPayment()->setCcExpYear(
                                isset($additionalInfo['ccExpYear']) ? $additionalInfo['ccExpYear'] : ""
                            );
                            $this->_order->getPayment()->setAdditionalInformation(
                                'token',
                                isset($additionalInfo['token']) ? $additionalInfo['token'] : ""
                            );
                            $this->_order->getPayment()->setAdditionalInformation(
                                'create_oneclick',
                                isset($additionalInfo['create_oneclick']) ? $additionalInfo['create_oneclick'] : 1
                            );
                            $this->_order->getPayment()->setAdditionalInformation(
                                'use_oneclick',
                                isset($additionalInfo['use_oneclick']) ? $additionalInfo['use_oneclick'] : 0
                            );
                            $this->_order->getPayment()->setAdditionalInformation(
                                'selected_oneclick_card',
                                isset($additionalInfo['selected_oneclick_card']) ? $additionalInfo['selected_oneclick_card'] : 0
                            );
                        }

                        //because only one nominal item in cart is authorized and Hipay not manage many profiles
                        return $this->_order;
                    }
                }

                Mage::throwException("An error occured. Profile Ids not present!");
            } else {
                $this->_order = Mage::getModel('sales/order')->load($this->getCheckout()->getLastOrderId());
            }
        }

        return $this->_order;
    }

    /**
     * Add method to calculate amount from recurring profile
     * @param Mage_Sales_Model_Recurring_Profile $profile
     * @return int $amount
     **/
    public function getAmountFromProfile(Mage_Sales_Model_Recurring_Profile $profile)
    {
        $amount = $profile->getBillingAmount() + $profile->getTaxAmount() + $profile->getShippingAmount();

        if ($this->isInitialProfileOrder($profile)) {
            $amount += $profile->getInitAmount();
        }

        return $amount;
    }

    protected function isInitialProfileOrder(Mage_Sales_Model_Recurring_Profile $profile)
    {
        if (!empty($profile->getChildOrderIds()) && current($profile->getChildOrderIds()) == "-1") {
            return true;
        }

        return false;
    }


    /**
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }


    public function updateDebitAmountAction()
    {
        /* @var $_helper Allopass_Hipay_Helper_Data */
        $_helper = Mage::helper('hipay');
        $response = array();
        $response['error'] = true;
        $response['success'] = false;

        $paymentProfileId = $this->getRequest()->getParam('payment_profile_id', false);
        $amount = $this->getCheckout()->getQuote()->getGrandTotal();
        $useOrderCurrency = Mage::getStoreConfig('hipay/hipay_api/currency_transaction', Mage::app()->getStore());

        if ($useOrderCurrency) {
            $currency = Mage::app()->getStore()->getCurrency();
        } else {
            $currency = Mage::app()->getStore()->getBaseCurrency();
        }

        $response['message'] = Mage::helper('hipay')->__(
            'You will be debited of  %s only after submitting order.',
            $currency->format($amount, array(), true)
        );

        if ($paymentProfileId) {
            try {
                $splitPayment = $_helper->splitPayment((int)$paymentProfileId, $amount);
                $response['success'] = true;
                $response['error'] = false;
                $response['splitPayment'] = $splitPayment;
                $response['grandTotal'] = $amount;
                $firstAmount = $splitPayment[0]['amountToPay'];
                array_shift($splitPayment);
                $otherPayments = "<p><span>"
                    . Mage::helper('hipay')->__("Your next payments:")
                    . '</span><table class="data-table" id="split-payment-cc-table">';
                foreach ($splitPayment as $value) {
                    $otherPayments .= '<tr>';
                    $amount = $currency->format($value['amountToPay'], array(), true);
                    $dateToPay = new Zend_Date($value['dateToPay']);
                    $otherPayments .= '<td>'
                        . $dateToPay->toString(Zend_Date::DATE_LONG)
                        . "</td><td> " . $amount . '</td>';
                    $otherPayments .= '</tr>';
                }

                $otherPayments .= '<table></p>';

                $response['labelSplitPayment'] = "<p><span>"
                    . Mage::helper('hipay')->__(
                        'You will be debited of  %s only after submitting order.',
                        $currency->format($firstAmount, array(), true)
                    )
                    . '</span></p>';
                $response['labelSplitPayment'] .= $otherPayments;
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }


        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
