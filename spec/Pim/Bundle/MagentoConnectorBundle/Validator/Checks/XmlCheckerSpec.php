<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Validator\Checks;

use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidXmlException;
use PhpSpec\ObjectBehavior;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class XmlCheckerSpec extends ObjectBehavior
{
    function it_fails_with_invalid_xml()
    {
        $exception = new InvalidXmlException();
        $invalidXml = '<note><to>Tove</Tto><from>Jani</Ffrom><heading>Reminder</Hheading><body>Don\'t forget me this weekend!</body>';

        $this->shouldThrow($exception)->duringCheckXml($invalidXml);
    }

    function it_returns_SimpleXMLElement_with_valid_xml()
    {
        $validXml = '<note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Don\'t forget me this weekend!</body></note>';

        $this->checkXml($validXml)->shouldReturn(null);
    }
}
