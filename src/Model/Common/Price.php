<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 * @created: 04.02.15, 16:43
 */

namespace Sphere\Core\Model\Common;

use Sphere\Core\Model\Channel\ChannelReference;
use Sphere\Core\Model\CustomerGroup\CustomerGroupReference;

/**
 * Class Price
 * @package Sphere\Core\Model\Common
 * @link http://dev.sphere.io/http-api-projects-products.html#product-price
 * @method static Price of(Money $value)
 * @method Money getValue()
 * @method string getCountry()
 * @method CustomerGroupReference getCustomerGroup()
 * @method ChannelReference getChannel()
 * @method DiscountedPrice getDiscounted()
 * @method Price setValue(Money $value = null)
 * @method Price setCountry(string $country = null)
 * @method Price setCustomerGroup(CustomerGroupReference $customerGroup = null)
 * @method Price setChannel(ChannelReference $channel = null)
 * @method Price setDiscounted(DiscountedPrice $discounted = null)
 * @method string getId()
 * @method Price setId(string $id = null)
 * @method DateTimeDecorator getValidFrom()
 * @method Price setValidFrom(\DateTime $validFrom = null)
 * @method DateTimeDecorator getValidUntil()
 * @method Price setValidUntil(\DateTime $validUntil = null)
 */
class Price extends JsonObject
{
    use OfTrait;

    public function getFields()
    {
        return [
            'id' => [static::TYPE => 'string'],
            'value' => [self::TYPE => '\Sphere\Core\Model\Common\Money'],
            'country' => [self::TYPE => 'string'],
            'customerGroup' => [self::TYPE => '\Sphere\Core\Model\CustomerGroup\CustomerGroupReference'],
            'channel' => [self::TYPE => '\Sphere\Core\Model\Channel\ChannelReference'],
            'validFrom' => [
                self::TYPE => '\DateTime',
                self::DECORATOR => '\Sphere\Core\Model\Common\DateTimeDecorator'
            ],
            'validUntil' => [
                self::TYPE => '\DateTime',
                self::DECORATOR => '\Sphere\Core\Model\Common\DateTimeDecorator'
            ],
            'discounted' => [self::TYPE => '\Sphere\Core\Model\Common\DiscountedPrice'],
        ];
    }

    /**
     * @param Money $value
     * @param Context|callable $context
     */
    public function __construct(Money $value, $context = null)
    {
        $this->setContext($context);
        $this->setValue($value);
    }

    /**
     * @param array $data
     * @param Context|callable $context
     * @return static
     */
    public static function fromArray(array $data, $context = null)
    {
        $price = new static(
            Money::fromArray($data['value'], $context),
            $context
        );
        $price->setRawData($data);

        return $price;
    }

    public function __toString()
    {
        return $this->getValue()->__toString();
    }
}