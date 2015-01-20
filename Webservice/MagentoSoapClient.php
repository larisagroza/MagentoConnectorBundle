<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Guesser\AbstractGuesser;

/**
 * A magento soap client to abstract interaction with the php soap client
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoSoapClient
{
    const MAGENTO_BAD_CREDENTIALS     = '2';
    const MAGENTO_CLIENT_NOT_CALLABLE = 'Client';

    protected $session;

    protected $client;

    protected $calls;

    protected $clientParameters;

    protected $logDir;

    /**
     * Create and init the soap client
     *
     * @param MagentoSoapClientParameters $clientParameters
     * @param string                      $logDir
     * @param \SoapClient                 $soapClient
     *
     * @throws ConnectionErrorException
     */
    public function __construct(
        MagentoSoapClientParameters $clientParameters,
        $logDir,
        \SoapClient $soapClient = null
    ) {
        $this->clientParameters = $clientParameters;
        $this->logDir           = $logDir;

        if (!$soapClient) {
            $wsdlUrl     = $this->clientParameters->getSoapUrl();
            $soapOptions = [
                'encoding'   => 'UTF-8',
                'trace'      => true,
                'exceptions' => true,
                'login'      => $this->clientParameters->getHttpLogin(),
                'password'   => $this->clientParameters->getHttpPassword(),
                'cache_wsdl' => WSDL_CACHE_BOTH
            ];

            try {
                $this->client = new \SoapClient($wsdlUrl, $soapOptions);
            } catch (\SoapFault $e) {
                throw new ConnectionErrorException(
                    'The soap connection could not be established',
                    $e->getCode(),
                    $e
                );
            }
        } else {
            $this->client = $soapClient;
        }

        $this->connect();
    }

    /**
     * Initialize the soap client with the local information
     *
     * @throws InvalidCredentialException If given credentials are invalid
     * @throws SoapCallException          If Magento is not accessible.
     * @throws \SoapFault
     */
    protected function connect()
    {
        try {
            $this->session = $this->client->login(
                $this->clientParameters->getSoapUsername(),
                $this->clientParameters->getSoapApiKey()
            );
        } catch (\SoapFault $e) {
            if (static::MAGENTO_BAD_CREDENTIALS === $e->faultcode) {
                throw new InvalidCredentialException(
                    sprintf(
                        'Error on Magento SOAP credentials to "%s": "%s".'.
                        'You should check your login and Magento API key.',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            } elseif (static::MAGENTO_CLIENT_NOT_CALLABLE === $e->faultcode) {
                $lastResponse = $this->client->__getLastResponse();
                echo "DEBUG: Last SOAP response: ".$lastResponse."\n";
                if (strlen($lastResponse) <= 200) {
                    $truncatedLastResponse  = htmlentities($lastResponse);
                } else {
                    $truncatedLastResponse = substr(htmlentities($lastResponse), 0, 100).
                        nl2br("\n...\n").substr(htmlentities($lastResponse), -100);
                }
                throw new SoapCallException(
                    sprintf(
                        'Error on Magento client to "%s": "%s".'.
                        'Something is probably wrong in the last SOAP response:'.nl2br("\n").'"%s"',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage(),
                        $truncatedLastResponse
                    ),
                    $e->getCode(),
                    $e
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * Is the client connected ?
     *
     * @return boolean
     */
    public function isConnected()
    {
        return (bool) $this->session;
    }

    /**
     * Call soap api
     *
     * @param string $resource
     * @param array  $params
     *
     * @return array
     *
     * @throws NotConnectedException
     * @throws SoapCallException
     */
    public function call($resource, $params = null)
    {
        if ($this->isConnected()) {
            try {
                $callStart = microtime(true);
                $response = $this->client->call($this->session, $resource, $params);
                $callEnd = microtime(true);
                $this->logProfileCalls($resource, $callStart, $callEnd);
            } catch (\SoapFault $e) {
                if ($resource === 'core_magento.info' && $e->getMessage()
                    === AbstractGuesser::MAGENTO_CORE_ACCESS_DENIED) {
                    $response = ['magento_version' => AbstractGuesser::UNKNOWN_VERSION];
                } elseif ($e->getMessage() === AbstractGuesser::MAGENTO_CORE_ACCESS_DENIED) {
                    throw new SoapCallException(
                        sprintf(
                            'Error on Magento soap call to "%s" : "%s" Called resource : "%s" with parameters : %s.' .
                            ' Soap user needs access on this resource. Please ' .
                            'check in your Magento webservice soap roles and ' .
                            'users configuration.',
                            $this->clientParameters->getSoapUrl(),
                            $e->getMessage(),
                            $resource,
                            json_encode($params)
                        ),
                        $e->getCode(),
                        $e
                    );
                } else {
                    throw new SoapCallException(
                        sprintf(
                            'Error on Magento soap call to "%s" : "%s". Called resource : "%s" with parameters : %s',
                            $this->clientParameters->getSoapUrl(),
                            $e->getMessage(),
                            $resource,
                            json_encode($params)
                        ),
                        $e->getCode(),
                        $e
                    );
                }
            }

            if (is_array($response) && isset($response['isFault']) && $response['isFault']) {
                throw new SoapCallException(
                    sprintf(
                        'Error on Magento soap call to "%s" : "%s". Called resource : "%s" with parameters : %s.' .
                        'Response from API : %s',
                        $this->clientParameters->getSoapUrl(),
                        $e->getMessage(),
                        $resource,
                        json_encode($params),
                        json_encode($response)
                    )
                );
            }

            return $response;
        } else {
            throw new NotConnectedException();
        }
    }

    /**
     * Add a call to the soap call stack
     *
     * @param array $call A magento soap call
     */
    public function addCall(array $call)
    {
        $this->call($call[0], $call[1]);
    }

    /**
     * Send pending calls to the magento soap api (with multiCall function)
     */
    public function sendCalls()
    {
        if (count($this->calls) > 0) {
            if ($this->isConnected()) {
                try {
                    $this->client->multiCall(
                        $this->session,
                        $this->calls
                    );
                } catch (\SoapFault $e) {
                    throw new SoapCallException(
                        sprintf(
                            'Error on Magento soap call : "%s". Called resources : "%s".',
                            $e->getMessage(),
                            json_encode($this->calls)
                        )
                    );
                }
            } else {
                throw new NotConnectedException();
            }

            $this->calls = [];
        }
    }

    /**
     * @param string $resource
     * @param string $start
     * @param string $end
     */
    protected function logProfileCalls($resource, $start, $end)
    {
        $duration = number_format(($end - $start), 3);
        $stepStartingDate = date("Y-m-d H:i:s", $start);
        $filePath = $this->logDir.'soap_profile.log';
        $this->writeCallLog($filePath, $stepStartingDate, $resource, $duration);
    }

    /**
     * @param string $filePath
     * @param string $stepStartingDate
     * @param string $resource
     * @param string $duration
     */
    protected function writeCallLog($filePath, $stepStartingDate, $resource, $duration)
    {
        $log = fopen($filePath, "a");

        if ([] === file($filePath)) {
            fwrite($log, "datetime;soap_call_resource;type;duration\n");
        }
        fwrite($log, $stepStartingDate.';'.$resource.';'.$duration."\n");
        fclose($log);
    }
}
