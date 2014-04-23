<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException;
use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Service\ClientInterface;

/**
 * Allows to explore a soap url
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SoapExplorer
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    /**
     * Reaches soap url and get his content
     *
     * @param string $soapUrl
     *
     * @return string Xml content as string
     *
     * @throws NotReachableUrlException
     * @throws InvalidSoapUrlException
     */
    public function getSoapUrlContent($soapUrl)
    {
        $request = $this->client->createRequest('GET', $soapUrl);

        try {
            $response = $this->client->send($request);
        } catch (CurlException $e) {
            throw new NotReachableUrlException;
        } catch (BadResponseException $e) {
            throw new InvalidSoapUrlException();
        }

        if (false === $response->isContentType('text/xml')) {
            throw new InvalidSoapUrlException();
        }

        return $response->getBody(true);
    }
}
