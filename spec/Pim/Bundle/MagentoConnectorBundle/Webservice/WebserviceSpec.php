<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClient;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use PhpSpec\ObjectBehavior;

class WebserviceSpec extends ObjectBehavior
{
    function let(MagentoSoapClient $magentoSoapClient)
    {
        $this->beConstructedWith($magentoSoapClient);
    }

    function it_calls_soap_client_to_send_new_category($magentoSoapClient)
    {
        $magentoSoapClient->call(
            Webservice::SOAP_ACTION_CATEGORY_CREATE,
            ['foo']
        )->willReturn(12);

        $this->sendNewCategory(['foo'])->shouldReturn(12);
    }

    function it_calls_soap_client_to_send_category_update($magentoSoapClient)
    {
        $magentoSoapClient->call(
            Webservice::SOAP_ACTION_CATEGORY_UPDATE,
            ['foo']
        )->shouldBeCalled();

        $this->sendUpdateCategory(['foo']);
    }

    function it_calls_soap_client_to_send_category_move($magentoSoapClient)
    {
        $magentoSoapClient->call(
            Webservice::SOAP_ACTION_CATEGORY_MOVE,
            ['foo']
        )->shouldBeCalled();

        $this->sendMoveCategory(['foo']);
    }

    function it_calls_soap_client_to_get_categories_status($magentoSoapClient)
    {
        $tree = [
            'category_id' => 1,
            'children' => [
                [
                    'category_id' => 3,
                    'children' => [],
                ],
            ],
        ];

        $flattenTree = [
            1 => [
                'category_id' => 1,
                'children' => [
                    [
                        'category_id' => 3,
                        'children' => [],
                    ],
                ],
            ],
            3 => [
                'category_id' => 3,
                'children' => [],
            ],
        ];

        $magentoSoapClient->call(
            Webservice::SOAP_ACTION_CATEGORY_TREE
        )->willReturn($tree);

        $this->getCategoriesStatus()->shouldReturn($flattenTree);
    }

    function it_gets_association_status_for_a_given_product($magentoSoapClient, ProductInterface $product)
    {
        $magentoSoapClient->call('catalog_product_link.list', ['up_sell', 'sku-012', 'sku'])->willReturn('up_sell');
        $magentoSoapClient->call('catalog_product_link.list', ['cross_sell', 'sku-012', 'sku'])->willReturn('cross_sell');
        $magentoSoapClient->call('catalog_product_link.list', ['related', 'sku-012', 'sku'])->willReturn('related');
        $magentoSoapClient->call('catalog_product_link.list', ['grouped', 'sku-012', 'sku'])->willReturn('grouped');

        $product->getIdentifier()->willReturn('sku-012');

        $this->getAssociationsStatus($product)->shouldReturn(
            [
                'up_sell'    => 'up_sell',
                'cross_sell' => 'cross_sell',
                'related'    => 'related',
                'grouped'    => 'grouped',
            ]
        );
    }

    function it_send_remove_call($magentoSoapClient)
    {
        $magentoSoapClient->call('catalog_product_link.remove', ['foo'])->shouldBeCalled();

        $this->removeProductAssociation(['foo']);
    }

    function it_send_create_call($magentoSoapClient)
    {
        $magentoSoapClient->call('catalog_product_link.assign', ['bar'])->shouldBeCalled();

        $this->createProductAssociation(['bar']);
    }

    function it_send_delete_call($magentoSoapClient)
    {
        $magentoSoapClient->call('catalog_product.delete', ['sku-000'])->shouldBeCalled();

        $this->deleteProduct('sku-000');
    }

    function it_send_disable_call($magentoSoapClient)
    {
        $magentoSoapClient->call('catalog_product.update', ['sku-001', ['status' => 2]])->shouldBeCalled();

        $this->disableProduct('sku-001');
    }
}
