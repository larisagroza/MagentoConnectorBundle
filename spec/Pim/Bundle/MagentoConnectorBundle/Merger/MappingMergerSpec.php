<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Merger;

use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\MagentoConnectorBundle\Mapper\Mapper;
use PhpSpec\ObjectBehavior;

class MappingMergerSpec extends ObjectBehavior
{
    function let(Mapper $mapper1, Mapper $mapper2)
    {
        $mapper1->getPriority()->willReturn(0);
        $mapper2->getPriority()->willReturn(10);
    }

    function it_gives_ordered_mapping_from_mappers($mapper1, $mapper2)
    {
        $this->beConstructedWith(array($mapper2, $mapper1), 'generic', 'export', true);

        $mapper1->getMapping()->willReturn(new MappingCollection(array(
            'source' => array(
                'source' => 'source',
                'target' => 'target1',
                'deletable' => true,
            ),
        )));

        $mapper2->getMapping()->willReturn(new MappingCollection(array(
            'source' => array(
                'source' => 'source',
                'target' => 'target2',
                'deletable' => true,
            ),
        )));

        $mappingCollection = $this->getMapping();
        $mappingCollection->toArray()->shouldBe(array(
            'source' => array(
                'source' => 'source',
                'target' => 'target2',
                'deletable' => true,
            ),
        ));
    }

    function it_sets_all_mappers_with_given_mapping($mapper1, $mapper2)
    {
        $this->beConstructedWith(array($mapper2, $mapper1), 'generic', 'export', true);

        $mapper1->setMapping(array('foo'))->shouldBeCalled();
        $mapper2->setMapping(array('foo'))->shouldBeCalled();

        $this->setMapping(array('foo'));
    }

    function it_gives_a_configuration_field($mapper1, $mapper2)
    {
        $this->beConstructedWith(array($mapper2, $mapper1), 'generic', 'export', true);

        $mapper1->getAllSources()->willReturn(array('id' => 'test', 'text' => 'Text3'));
        $mapper2->getAllSources()->willReturn(array('id' => 'test', 'text' => 'Text4'));

        $mapper1->getAllTargets()->willReturn(array('id' => 'test', 'text' => 'Text1'));
        $mapper2->getAllTargets()->willReturn(array('id' => 'test', 'text' => 'Text2'));

        $this->getConfigurationField()->shouldReturn(array(
            'genericMapping' => array(
                'type'    => 'textarea',
                'options' => array(
                    'required' => false,
                    'attr'     => array(
                        'class' => 'mapping-field',
                        'data-sources' => '{"sources":{"id":"test","text":"Text4"}}',
                        'data-targets' => '{"targets":{"id":"test","text":"Text2"},"allowAddition":true}',
                        'data-name'    => 'generic',
                    ),
                    'label' => 'pim_connector_mapping.export.genericMapping.label',
                    'help'  => 'pim_connector_mapping.export.genericMapping.help',
                ),
            ),
        ));
    }
}
