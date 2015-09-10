<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Model\TaxCategory;

use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\Common\JsonObject;

/**
 * @package Commercetools\Core\Model\TaxCategory
 * @method string getName()
 * @method TaxCategoryDraft setName(string $name = null)
 * @method string getDescription()
 * @method TaxCategoryDraft setDescription(string $description = null)
 * @method TaxRateCollection getRates()
 * @method TaxCategoryDraft setRates(TaxRateCollection $rates = null)
 */
class TaxCategoryDraft extends JsonObject
{
    public function fieldDefinitions()
    {
        return [
            'name' => [static::TYPE => 'string'],
            'description' => [static::TYPE => 'string'],
            'rates' => [static::TYPE => '\Commercetools\Core\Model\TaxCategory\TaxRateCollection'],
        ];
    }

    /**
     * @param string $name
     * @param TaxRateCollection $rates
     * @param Context|callable $context
     * @return TaxCategoryDraft
     */
    public static function ofNameAndRates($name, TaxRateCollection $rates, $context = null)
    {
        return static::of($context)->setName($name)->setRates($rates);
    }
}
