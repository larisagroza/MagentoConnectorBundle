<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\UrlExplorer;
use Guzzle\Service\ClientInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Common\Collection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UrlExplorerSpec extends ObjectBehavior
{
    function let(
        ClientInterface                     $client,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters         $clientParameters
    ) {
        $this->beConstructedWith($client);

        $clientParametersRegistry->getInstance(null, null, null, null, null, null, null)->willReturn($clientParameters);
    }

    function it_success_with_valid_soap_url(
        $client,
        $clientParameters,
        Request $request,
        Response $response,
        Collection $curlOptions
    ) {
        $guzzleParams = [
            'connect_timeout' => UrlExplorer::CONNECT_TIMEOUT,
            'timeout'         => UrlExplorer::TIMEOUT,
            'auth'            => [null, null],
        ];

        $clientParameters->getHash()->willReturn('some_hash');
        $clientParameters->getHttpLogin()->willReturn(null);
        $clientParameters->getHttpPassword()->willReturn(null);
        $clientParameters->getSoapUrl()->willReturn('http://myvalidsoap.url/api/soap/?wsdl');

        $client->get('http://myvalidsoap.url/api/soap/?wsdl', [], $guzzleParams)->willReturn($request);
        $request->getCurlOptions()->willReturn($curlOptions);
        $curlOptions->set(CURLOPT_CONNECTTIMEOUT, UrlExplorer::CONNECT_TIMEOUT)->willReturn($request);
        $curlOptions->set(CURLOPT_TIMEOUT, UrlExplorer::TIMEOUT)->willReturn($request);
        $client->send($request)->shouldBeCalled()->willReturn($response);

        $response->setHeader('ContentType', 'text/xml');
        $response->isContentType('text/xml')->shouldBeCalled()->willReturn(true);
        $response->getBody(true)->shouldBeCalled()->willReturn('<xml>Some xml as a string</xml>');

        $this->getUrlContent($clientParameters)->shouldReturn('<xml>Some xml as a string</xml>');
    }

    function it_success_with_valid_http_authentication_credentials(
        $client,
        $clientParameters,
        Request $request,
        Response $response,
        Collection $curlOptions
    ) {
        $guzzleParams = [
            'connect_timeout' => UrlExplorer::CONNECT_TIMEOUT,
            'timeout'         => UrlExplorer::TIMEOUT,
            'auth'            => ['http_login', 'password'],
        ];

        $clientParameters->getHash()->willReturn('some_hash');
        $clientParameters->getHttpLogin()->willReturn('http_login');
        $clientParameters->getHttpPassword()->willReturn('password');
        $clientParameters->getSoapUrl()->willReturn('http://myvalidsoap.url/api/soap/?wsdl');

        $client->get('http://myvalidsoap.url/api/soap/?wsdl', [], $guzzleParams)->willReturn($request);
        $request->getCurlOptions()->willReturn($curlOptions);
        $curlOptions->set(CURLOPT_CONNECTTIMEOUT, UrlExplorer::CONNECT_TIMEOUT)->willReturn($request);
        $curlOptions->set(CURLOPT_TIMEOUT, UrlExplorer::TIMEOUT)->willReturn($request);
        $client->send($request)->shouldBeCalled()->willReturn($response);

        $response->setHeader('ContentType', 'text/xml');
        $response->isContentType('text/xml')->shouldBeCalled()->willReturn(true);
        $response->getBody(true)->shouldBeCalled()->willReturn('<xml>Some xml as a string</xml>');

        $this->getUrlContent($clientParameters)->shouldReturn('<xml>Some xml as a string</xml>');
    }

    function it_fails_with_invalid_url(
        $client,
        $clientParameters,
        Request $request,
        Response $response,
        Collection $curlOptions
    ) {
        $guzzleParams = [
            'connect_timeout' => UrlExplorer::CONNECT_TIMEOUT,
            'timeout'         => UrlExplorer::TIMEOUT,
            'auth'            => [null, null],
        ];

        $clientParameters->getHash()->willReturn('some_hash');
        $clientParameters->getHttpLogin()->willReturn(null);
        $clientParameters->getHttpPassword()->willReturn(null);
        $clientParameters->getSoapUrl()->willReturn('http://notvalidurlapi/soap/?wsdl');

        $client->get('http://notvalidurlapi/soap/?wsdl', [], $guzzleParams)->willReturn($request);
        $request->getCurlOptions()->willReturn($curlOptions);
        $curlOptions->set(CURLOPT_CONNECTTIMEOUT, UrlExplorer::CONNECT_TIMEOUT)->willReturn($request);
        $curlOptions->set(CURLOPT_TIMEOUT, UrlExplorer::TIMEOUT)->willReturn($request);
        $client->send($request)->shouldBeCalled()->willThrow('Guzzle\Http\Exception\CurlException');

        $response->isContentType(Argument::any())->shouldNotBeCalled();
        $response->getBody(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException')->duringGetUrlContent($clientParameters);
    }

    function it_fails_with_invalid_http_authentication_credentials(
        $client,
        $clientParameters,
        Request $request,
        Response $response,
        Collection $curlOptions
    ) {
        $guzzleParams = [
            'connect_timeout' => UrlExplorer::CONNECT_TIMEOUT,
            'timeout'         => UrlExplorer::TIMEOUT,
            'auth'            => ['bad_login', 'passwd'],
        ];

        $clientParameters->getHash()->willReturn('some_hash');
        $clientParameters->getHttpLogin()->willReturn('bad_login');
        $clientParameters->getHttpPassword()->willReturn('passwd');
        $clientParameters->getSoapUrl()->willReturn('http://myvalidsoap.url/api/soap/?wsdl');

        $client->get('http://myvalidsoap.url/api/soap/?wsdl', [], $guzzleParams)->willReturn($request);
        $request->getCurlOptions()->willReturn($curlOptions);
        $curlOptions->set(CURLOPT_CONNECTTIMEOUT, UrlExplorer::CONNECT_TIMEOUT)->willReturn($request);
        $curlOptions->set(CURLOPT_TIMEOUT, UrlExplorer::TIMEOUT)->willReturn($request);
        $client->send($request)->shouldBeCalled()->willThrow('Guzzle\Http\Exception\BadResponseException');

        $response->isContentType(Argument::any())->shouldNotBeCalled();
        $response->getBody(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException')->duringGetUrlContent($clientParameters);
    }
}
