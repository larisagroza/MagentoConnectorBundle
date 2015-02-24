<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\AttributeOption;

/**
 * A normalizer to transform a option entity into an array
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class OptionNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $label = [
            [
                'store_id' => '0',
                'value'    => $object->getCode(),
            ],
            [
                'store_id' => '1',
                'value'    => $this->getOptionLabel($object, $context['defaultLocale'])
            ],
        ];

        foreach ($this->getOptionLocales($object) as $locale) {
            $storeView = $this->getStoreViewForLocale(
                $locale,
                $context['magentoStoreViews'],
                $context['storeViewMapping']
            );

            if ($storeView) {
                $label[] = [
                    'store_id' => (string) $storeView['store_id'],
                    'value'    => $this->getOptionLabel(
                        $object,
                        $locale,
                        $context['defaultLocale']
                    ),
                ];
            }
        }

        return [
            $context['attributeCode'],
            [
                'label'      => $label,
                'order'      => $object->getSortOrder()
            ]
        ];
    }

    /**
     * get options locale
     * @param AttributeOption $option
     *
     * @return array
     */
    protected function getOptionLocales(AttributeOption $option)
    {
        $locales = [];

        foreach ($option->getOptionValues() as $optionValue) {
            $locales[] = $optionValue->getLocale();
        }

        return $locales;
    }

    /**
     * Get option translation for given locale code
     * @param AttributeOption $option
     * @param string          $locale
     * @param string          $defaultLocale
     *
     * @return mixed
     */
    protected function getOptionLabel(AttributeOption $option, $locale, $defaultLocale = null)
    {
        $optionValue = $option->setLocale($locale)->getOptionValue();

        if (!$optionValue) {
            if ($defaultLocale) {
                return $this->getOptionTranslation($option, $defaultLocale);
            } else {
                return $option->getCode();
            }
        } else {
            return $optionValue->getLabel();
        }
    }
}
