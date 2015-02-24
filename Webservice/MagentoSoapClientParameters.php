<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

/**
 * Magento soap client parameters
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoSoapClientParameters
{
    const SOAP_WSDL_URL = '/api/soap/?wsdl';

    /**
     * @var string Soap Username
     */
    protected $soapUsername;

    /**
     * @var string Soap Api Key
     */
    protected $soapApiKey;

    /**
     * @var string Wsdl extension
     */
    protected $wsdlUrl;

    /**
     * @var string Magento Url (only the domain)
     */
    protected $magentoUrl;

    /**
     * @var string Default store view
     */
    protected $defaultStoreView;

    /**
     * @var string Login for http authentication
     */
    protected $httpLogin;

    /**
     * @var string Password for http authentication
     */
    protected $httpPassword;

    /**
     * @var boolean Are parameters valid or not ?
     */
    protected $isValid;

    /**
     * Constructor
     *
     * @param string $soapUsername     Magento soap username
     * @param string $soapApiKey       Magento soap api key
     * @param string $magentoUrl       Magento url (only the domain)
     * @param string $wsdlUrl          Only wsdl soap api extension
     * @param string $defaultStoreView Default stroe view
     * @param string $httpLogin        Login http authentication
     * @param string $httpPassword     Password http authentication
     */
    public function __construct(
        $soapUsername,
        $soapApiKey,
        $magentoUrl,
        $wsdlUrl,
        $defaultStoreView,
        $httpLogin = null,
        $httpPassword = null
    ) {
        $this->soapUsername     = $soapUsername;
        $this->soapApiKey       = $soapApiKey;
        $this->magentoUrl       = $magentoUrl;
        $this->wsdlUrl          = $wsdlUrl;
        $this->defaultStoreView = $defaultStoreView;
        $this->httpLogin        = $httpLogin;
        $this->httpPassword     = $httpPassword;
    }

    /**
     * get hash to uniquely identify parameters even in different instances
     *
     * @return string
     */
    public function getHash()
    {
        return md5(
            $this->soapUsername.
            $this->soapApiKey.
            $this->magentoUrl.
            $this->wsdlUrl.
            $this->defaultStoreView.
            $this->httpLogin.
            $this->httpPassword
        );
    }

    /**
     * Are parameters valid or not ?
     *
     * @return boolean Is valid
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Allows to change the state of validation
     *
     * @param boolean $state
     */
    public function setValidation($state)
    {
        $this->isValid = $state;
    }

    /**
     * Get soapUsername
     *
     * @return string Soap magento soapUsername
     */
    public function getSoapUsername()
    {
        return $this->soapUsername;
    }

    /**
     * Get the soap api key
     *
     * @return string Soap magento soapApiKey
     */
    public function getSoapApiKey()
    {
        return $this->soapApiKey;
    }

    /**
     * Get the soap Url
     *
     * @return string Soap Url
     */
    public function getSoapUrl()
    {
        return $this->magentoUrl.$this->wsdlUrl;
    }

    /**
     * Get the wsdl Url
     *
     * @return string Wsdl Url
     */
    public function getWsdlUrl()
    {
        return $this->wsdlUrl;
    }

    /**
     * Get the magento Url
     *
     * @return string Magento Domain Url
     */
    public function getMagentoUrl()
    {
        return $this->magentoUrl;
    }

    /**
     * Get the default store view
     *
     * @return string Default store view
     */
    public function getDefaultstoreView()
    {
        return $this->defaultStoreView;
    }

    /**
     * Get the http authentication login
     *
     * @return string Http login
     */
    public function getHttpLogin()
    {
        return $this->httpLogin;
    }

    /**
     * Get the http authentication password
     *
     * @return string Http password
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }
}
