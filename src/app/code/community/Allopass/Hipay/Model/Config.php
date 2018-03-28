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
class Allopass_Hipay_Model_Config extends Varien_Object
{
    const API_USERNAME = 'api_username';

    const API_PASSWORD = 'api_password';

    const API_TOKENJS_USERNAME = 'api_tokenjs_username';

    const API_TOKENJS_PUBLICKEY = 'api_tokenjs_publickey';

    const API_USERNAME_TEST = "api_username_test";

    const API_PASSWORD_TEST = 'api_password_test';

    const API_TOKENJS_USERNAME_TEST = 'api_tokenjs_username_test';

    const API_TOKENJS_PUBLICKEY_TEST = 'api_tokenjs_publickey_test';

    const SECRET_PASSPHRASE = 'secret_passphrase';

    const SECRET_PASSPHRASE_TEST = 'secret_passphrase_test';

    const VAULT_ENDPOINT_TEST = 'vault_endpoint_stage';

    const VAULT_ENDPOINT = 'vault_endpoint_production';

    const GATEWAY_ENDPOINT_TEST = 'gateway_endpoint_stage';

    const GATEWAY_ENDPOINT = 'gateway_endpoint_production';

    const DEBUG_MODE = 'debug';


    /**
     *  Use as Helper
     *
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @return      mixed
     */
    public function getConfigData($key, $storeId = null)
    {
        return $this->getInternalConfig('hipay_api', $key, $storeId);
    }

    /**
     *  Set config data
     *
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @param    int $scope Scope
     * @return   Mage_Core_Store_Config
     */
    public function setConfigData($key, $value, $storeId = null, $scope = 'default')
    {
        return Mage::getConfig()->saveConfig('hipay/hipay_api/' . $key, $value, $scope, $storeId);
    }

    /**
     *  Internal to get config and cache it
     *
     * @param    string $key context key
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @return      mixed
     */
    private function getInternalConfig($key_api, $key, $storeId)
    {
        $index = 'hipay' . $key_api . $key . $storeId;
        if (!$this->hasData($index)) {
            $value = Mage::getStoreConfig('hipay/' . $key_api . '/' . $key, $storeId);
            $this->setData($index, $value);
        }
        return $this->getData($index);
    }

    /**
     *  Return config NORMAL ( HIPAY_API )
     *
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @return      mixed
     */
    public function getConfig($key, $storeId = null)
    {
        return $this->getInternalConfig('hipay_api', $key, $storeId);
    }

    /**
     *  Return config MOTO ( HIPAY_MOTO)
     *
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @return      mixed
     */
    public function getConfigDataMoto($key, $storeId = null)
    {
        return $this->getInternalConfig('hipay_api_moto', $key, $storeId);
    }

    /**
     *  Return config BASKET
     *
     * @param    string $key Path key
     * @param    int $storeId Store View Id
     * @return   mixed
     */
    public function getConfigDataBasket($key, $storeId = null)
    {
        $basket = $this->getInternalConfig('hipay_basket', $key, $storeId);
        if (!$basket && !is_null($storeId)) {
            $basket = $this->getInternalConfig('hipay_basket', $key, null);
        }

        return $basket;
    }

    /**
     *  Return config for hashing algorithm
     *
     * @param    int $storeId Store View Id
     * @return   mixed
     */
    public function getConfigHashing($environment, $storeId = null)
    {
        $path = 'hipay/hipay_hash_algorithm/' . $environment;
        $config = Mage::getStoreConfig($path, $storeId);
        if (!$config && !is_null($storeId)) {
            $config = Mage::getStoreConfig($path);
        }

        return $config;
    }

    /**
     * Set config data for specific config
     *
     * @param $environment
     * @param $value
     * @param int $storeId
     * @param string $scope
     * @return Mage_Core_Store_Config
     */
    public function setConfigDataHashing($environment, $value, $storeId = null, $scope = 'default')
    {
        $path = 'hipay/hipay_hash_algorithm/' . $environment;
        return Mage::getConfig()->saveConfig($path, $value, $scope, $storeId);
    }

    /**
     *  Return config var
     *
     * @param    string $key Var path key
     * @param    int $storeId Store View Id
     * @return   mixed
     */
    public function getConfigFlag($key, $storeId = null)
    {
        if (!$this->hasData($key)) {
            $value = Mage::getStoreConfigFlag('hipay/hipay_api/' . $key, $storeId);
            $this->setData($key, $value);
        }
        return $this->getData($key);
    }

    public function getSecretPassphrase($storeId = null)
    {
        return $this->getConfigData(self::SECRET_PASSPHRASE, $storeId);
    }

    public function getSecretPassphraseTest($storeId = null)
    {
        return $this->getConfigData(self::SECRET_PASSPHRASE_TEST, $storeId);
    }

    public function getSecretPassphraseMoto($storeId = null)
    {
        if ($this->getConfigDataMoto(self::SECRET_PASSPHRASE, $storeId)) {
            return $this->getConfigDataMoto(self::SECRET_PASSPHRASE, $storeId);
        }
        return $this->getConfigData(self::SECRET_PASSPHRASE, $storeId);
    }

    public function getSecretPassphraseTestMoto($storeId = null)
    {
        if ($this->getConfigDataMoto(self::SECRET_PASSPHRASE_TEST, $storeId)) {
            return $this->getConfigDataMoto(self::SECRET_PASSPHRASE_TEST, $storeId);
        }
        return $this->getConfigData(self::SECRET_PASSPHRASE_TEST, $storeId);
    }

    public function getApiUsername($storeId = null)
    {
        return $this->getConfigData(self::API_USERNAME, $storeId);
    }

    public function getApiPassword($storeId = null)
    {
        return $this->getConfigData(self::API_PASSWORD, $storeId);
    }

    public function getApiTokenJSUsername($storeId = null)
    {
        return $this->getConfigData(self::API_TOKENJS_USERNAME, $storeId);
    }

    public function getApiTokenJSPublickey($storeId = null)
    {
        return $this->getConfigData(self::API_TOKENJS_PUBLICKEY, $storeId);
    }

    public function getApiUsernameTest($storeId = null)
    {
        return $this->getConfigData(self::API_USERNAME_TEST, $storeId);
    }

    public function getApiPasswordTest($storeId = null)
    {
        return $this->getConfigData(self::API_PASSWORD_TEST, $storeId);
    }

    public function getApiTokenJSUsernameTest($storeId = null)
    {
        return $this->getConfigData(self::API_TOKENJS_USERNAME_TEST, $storeId);
    }

    public function getApiTokenJSPublickeyTest($storeId = null)
    {
        return $this->getConfigData(self::API_TOKENJS_PUBLICKEY_TEST, $storeId);
    }

    public function getVaultEndpoint($storeId = null)
    {
        return $this->getConfigData(self::VAULT_ENDPOINT, $storeId);
    }

    public function getVaultEndpointTest($storeId = null)
    {
        return $this->getConfigData(self::VAULT_ENDPOINT_TEST, $storeId);
    }

    public function getGatewayEndpoint($storeId = null)
    {
        return $this->getConfigData(self::GATEWAY_ENDPOINT, $storeId);
    }

    public function getGatewayEndpointTest($storeId = null)
    {
        return $this->getConfigData(self::GATEWAY_ENDPOINT_TEST, $storeId);
    }

    public function getApiUsernameMoto($storeId = null)
    {
        return $this->getConfigDataMoto(self::API_USERNAME, $storeId);
    }

    public function getApiPasswordMoto($storeId = null)
    {
        return $this->getConfigDataMoto(self::API_PASSWORD, $storeId);
    }

    public function getApiUsernameTestMoto($storeId = null)
    {
        return $this->getConfigDataMoto(self::API_USERNAME_TEST, $storeId);
    }

    public function getApiPasswordTestMoto($storeId = null)
    {
        return $this->getConfigDataMoto(self::API_PASSWORD_TEST, $storeId);
    }

    /**
     *  General log level
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function isGeneralDebugEnabled($storeId = null)
    {
        return $this->getConfigData(self::DEBUG_MODE, $storeId);
    }


    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('global/payment_hipay/cc/types')->asArray();

        uasort($_types, array('Allopass_Hipay_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }
        return $types;
    }

    /**
     * Retrieve array of credit card types to get code hipay equals to code Magento
     *
     * @return array
     */
    public function getCcTypesHipay()
    {
        $_types = Mage::getConfig()->getNode('global/payment_hipay/hosted/types')->asArray();

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['code_hipay'])) {
                $types[$data['code']] = $data['code_hipay'];
            }
        }
        return $types;
    }

    /**
     * Retrieve array of template types to display in hosted page
     *
     * @return array
     */
    public function getTemplateHosted()
    {
        $_templates = Mage::getConfig()->getNode('global/template_hipay/hosted')->asArray();

        $templates = array();
        foreach ($_templates as $data) {
            if (isset($data['value']) && isset($data['label'])) {
                $templates[$data['value']] = $data['label'];
            }
        }
        return $templates;
    }

    /**
     * Retrieve array of credit card types to get code hipay
     *
     * @return array
     */
    public function getCcTypesCodeHipay()
    {
        $_types = Mage::getConfig()->getNode('global/payment_hipay/hosted/types')->asArray();

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code_hipay']) && isset($data['name'])) {
                $types[$data['code_hipay']] = $data['name'];
            }
        }
        return $types;
    }

    /**
     * Retrieve list of months translation
     *
     * @return array
     */
    public function getMonths()
    {
        $data = Mage::app()->getLocale()->getTranslationList('month');
        foreach ($data as $key => $value) {
            $monthNum = ($key < 10) ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * Retrieve array of available years
     *
     * @return array
     */
    public function getYears()
    {
        $years = array();
        $first = date("Y");

        for ($index = 0; $index <= 10; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Statis Method for compare sort order of CC Types
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function compareCcTypes($a, $b)
    {
        if (!isset($a['order'])) {
            $a['order'] = 0;
        }

        if (!isset($b['order'])) {
            $b['order'] = 0;
        }

        if ($a['order'] == $b['order']) {
            return 0;
        } elseif ($a['order'] > $b['order']) {
            return 1;
        } else {
            return -1;
        }
    }
}
