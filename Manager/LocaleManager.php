<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Manager\LocaleManager as BaseLocaleManager;

/**
 * @Deprecated
 *
 * Custom locale manager
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class LocaleManager
{
    /** @var BaseLocaleManager $baseLocaleManager */
    protected $baseLocaleManager;

    /**
     * @param BaseLocaleManager $baseLocaleManager
     */
    public function __construct(BaseLocaleManager $baseLocaleManager)
    {
        $this->baseLocaleManager = $baseLocaleManager;
    }

    /**
     * @Deprecated
     *
     * Get locale choices
     * Allow to list locales in an array like array[<code>] = <code>
     *
     * @return string[]
     */
    public function getLocaleChoices()
    {
        $codes = $this->baseLocaleManager->getActiveCodes();

        $choices = [];
        foreach ($codes as $code) {
            $choices[$code] = $code;
        }

        return $choices;
    }

    /**
     * Get active locales.
     *
     * @return Locale[]
     */
    public function getActiveLocales()
    {
        return $this->baseLocaleManager->getActiveLocales();
    }

    /**
     * Get disabled locales.
     *
     * @return Locale[]
     */
    public function getDisabledLocales()
    {
        return $this->baseLocaleManager->getDisabledLocales();
    }

    /**
     * Get locales with criterias.
     *
     * @param array $criterias
     *
     * @return Locale[]
     */
    public function getLocales($criterias = array())
    {
        return $this->baseLocaleManager->getLocales($criterias);
    }

    /**
     * Get locale by code.
     *
     * @param string $code
     *
     * @return Locale
     */
    public function getLocaleByCode($code)
    {
        return $this->baseLocaleManager->getLocaleByCode($code);
    }

    /**
     * Get active codes.
     *
     * @return string[]
     */
    public function getActiveCodes()
    {
        return $this->baseLocaleManager->getActiveCodes();
    }
}
