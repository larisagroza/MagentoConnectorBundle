<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Validator\Constraints;

use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\UrlExplorer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Validator\Checks\XmlChecker;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;
use Pim\Bundle\MagentoConnectorBundle\Item\MagentoItemStep;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * Specification of class HasValidCredentialsValidator
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HasValidCredentialsValidatorSpec extends ObjectBehavior
{
    function let(
        WebserviceGuesser $webserviceGuesser,
        UrlExplorer $urlExplorer,
        XmlChecker $xmlChecker,
        ExecutionContextInterface $context,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        MagentoSoapClientParameters $clientParameters
    ) {
        $this->beConstructedWith($webserviceGuesser, $urlExplorer, $xmlChecker, $clientParametersRegistry);

        $clientParametersRegistry->getInstance(null, null, null, null, null, null, null)->willReturn($clientParameters);

        $this->initialize($context);
    }

    function it_does_nothing_with_something_else_than_magento_item_step(
        $context,
        AbstractConfigurableStepElement $step,
        Constraint $constraint
    ) {
        $context->addViolationAt(Argument::cetera())->shouldNotBeCalled();

        $this->validate($step, $constraint);
    }

    function it_success_with_good_credentials_and_valid_soap_url(
        $context,
        $clientParameters,
        MagentoItemStep $step,
        Constraint $constraint
    ) {
        $clientParameters->isValid()->willReturn(true);
        $context->addViolationAt(Argument::cetera())->shouldNotBeCalled();
        $clientParameters->setValidation(Argument::any())->shouldNotBeCalled();

        $this->validate($step, $constraint);
    }

    function it_fails_if_soap_url_is_not_reachable(
        $clientParameters,
        $context,
        $urlExplorer,
        MagentoItemStep $step,
        HasValidCredentials $constraint
    ) {
        $clientParameters->setValidation(false);
        $constraint->messageUrlNotReachable = 'pim_magento_connector.export.validator.url_not_reachable';
        $urlExplorer->getUrlContent($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException');
        $context->addViolationAt('wsdlUrl', 'pim_magento_connector.export.validator.url_not_reachable ""')->shouldBeCalled();

        $this->validate($step, $constraint);
    }

    function it_fails_with_invalid_soap_url_or_wrong_http_authentication_credentials(
        $context,
        $clientParameters,
        $urlExplorer,
        MagentoItemStep $step,
        HasValidCredentials $constraint
    ) {
        $clientParameters->setValidation(false);
        $constraint->messageSoapNotValid = 'pim_magento_connector.export.validator.soap_url_not_valid';
        $urlExplorer->getUrlContent($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException');
        $context->addViolationAt('wsdlUrl', 'pim_magento_connector.export.validator.soap_url_not_valid ""')->shouldBeCalled();

        $this->validate($step, $constraint);
    }

    function it_fails_with_invalid_soap_xml_return(
        $context,
        $clientParameters,
        $xmlChecker,
        MagentoItemStep $step,
        HasValidCredentials $constraint
    ) {
        $clientParameters->setValidation(false);
        $constraint->messageXmlNotValid = 'pim_magento_connector.export.validator.xml_not_valid';
        $xmlChecker->checkXml(Argument::any())->willThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidXmlException');
        $context->addViolationAt('wsdlUrl', 'pim_magento_connector.export.validator.xml_not_valid')->shouldBeCalled();

        $this->validate($step, $constraint);
    }

    function it_fails_with_invalid_soap_credentials_or_user_has_no_right_on_magento(
        $context,
        $clientParameters,
        $webserviceGuesser,
        MagentoItemStep $step,
        HasValidCredentials $constraint
    ) {
        $clientParameters->setValidation(false);
        $constraint->messageUsername = 'pim_magento_connector.export.validator.authentication_failed';
        $webserviceGuesser->getWebservice($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\InvalidCredentialException');
        $context->addViolationAt('soapUsername', 'pim_magento_connector.export.validator.authentication_failed')->shouldBeCalled();

        $this->validate($step, $constraint);
    }

    function it_fails_if_an_unknown_error_occured(
        $context,
        $clientParameters,
        $webserviceGuesser,
        MagentoItemStep $step,
        HasValidCredentials $constraint
    ) {
        $clientParameters->setValidation(false);
        $webserviceGuesser->getWebservice($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');
        $context->addViolationAt('soapUsername', Argument::any())->shouldBeCalled();

        $this->validate($step, $constraint);
    }

    function it_returns_true_with_good_credentials_and_valid_soap_url(
        $clientParameters,
        $urlExplorer,
        $webserviceGuesser,
        Webservice $webservice
    ) {
        $clientParameters->isValid()->willReturn(null);
        $urlExplorer->getUrlContent($clientParameters)->willReturn('<content>Some xml as string</content>');
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $webservice->getStoreViewsList()->willReturn(array());
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(true);
    }

    function it_returns_false_with_invalid_soap_url_or_wrong_http_authentication_credentials(
        $urlExplorer,
        $clientParameters,
        $webserviceGuesser
    ) {
        $clientParameters->isValid()->willReturn(null);
        $urlExplorer->getUrlContent($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException');
        $webserviceGuesser->getWebservice($clientParameters)->shouldNotBeCalled();
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(false);
    }

    function it_returns_false_if_soap_url_is_not_reachable(
        $urlExplorer,
        $clientParameters,
        $webserviceGuesser
    ) {
        $clientParameters->isValid()->willReturn(null);
        $urlExplorer->getUrlContent($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException');
        $webserviceGuesser->getWebservice($clientParameters)->shouldNotBeCalled();
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(false);
    }

    function it_returns_false_with_invalid_soap_credentials(
        $webserviceGuesser,
        $clientParameters
    ) {
        $clientParameters->isValid()->willReturn(null);
        $webserviceGuesser->getWebservice($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\InvalidCredentialException');
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(false);
    }

    function it_returns_false_if_user_has_no_right_on_magento(
        $webserviceGuesser,
        $clientParameters,
        Webservice $webservice
    ) {
        $clientParameters->isValid()->willReturn(null);
        $webserviceGuesser->getWebservice($clientParameters)->willReturn($webservice);
        $webservice->getStoreViewsList()->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(false);
    }

    function it_returns_false_if_an_unknown_error_occured(
        $webserviceGuesser,
        $clientParameters
    ) {
        $clientParameters->isValid()->willReturn(null);
        $webserviceGuesser->getWebservice($clientParameters)->willThrow('\Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException');
        $clientParameters->setValidation(Argument::type('bool'))->will(function (array $args) {
            $this->isValid()->willReturn($args[0]);
        });
        $this->areValidSoapCredentials($clientParameters)->shouldReturn(false);
    }
}
