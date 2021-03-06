<?php
/**
 * @author @jenschude <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Model\Subscription;

use Commercetools\Core\Model\Common\Reference;
use Commercetools\Core\Model\Common\DateTimeDecorator;
use Commercetools\Core\Model\Message\Message;
use DateTime;
use Commercetools\Core\Model\Message\UserProvidedIdentifiers;

/**
 * @package Commercetools\Core\Model\Subscription
 * @method string getProjectKey()
 * @method MessageDelivery setProjectKey(string $projectKey = null)
 * @method string getNotificationType()
 * @method MessageDelivery setNotificationType(string $notificationType = null)
 * @method Reference getResource()
 * @method MessageDelivery setResource(Reference $resource = null)
 * @method string getId()
 * @method MessageDelivery setId(string $id = null)
 * @method int getVersion()
 * @method MessageDelivery setVersion(int $version = null)
 * @method int getSequenceNumber()
 * @method MessageDelivery setSequenceNumber(int $sequenceNumber = null)
 * @method int getResourceVersion()
 * @method MessageDelivery setResourceVersion(int $resourceVersion = null)
 * @method DateTimeDecorator getCreatedAt()
 * @method MessageDelivery setCreatedAt(DateTime $createdAt = null)
 * @method DateTimeDecorator getLastModifiedAt()
 * @method MessageDelivery setLastModifiedAt(DateTime $lastModifiedAt = null)
 * @method PayloadNotIncluded getPayloadNotIncluded()
 * @method MessageDelivery setPayloadNotIncluded(PayloadNotIncluded $payloadNotIncluded = null)
 * @method UserProvidedIdentifiers getResourceUserProvidedIdentifiers()
 * phpcs:disable
 * @method MessageDelivery setResourceUserProvidedIdentifiers(UserProvidedIdentifiers $resourceUserProvidedIdentifiers = null)
 * phpcs:enable
 */
class MessageDelivery extends Delivery
{
    public function fieldDefinitions()
    {
        $definition = parent::fieldDefinitions();
        $definition = array_merge(
            $definition,
            [
                'id' => [static::TYPE => 'string'],
                'version' => [static::TYPE => 'int'],
                'sequenceNumber' => [static::TYPE => 'int'],
                'resourceVersion' => [static::TYPE => 'int'],
                'createdAt' => [
                    static::TYPE => DateTime::class,
                    static::DECORATOR => DateTimeDecorator::class
                ],
                'lastModifiedAt' => [
                    static::TYPE => DateTime::class,
                    static::DECORATOR => DateTimeDecorator::class
                ],
                'payloadNotIncluded' => [static::TYPE => PayloadNotIncluded::class],
            ]
        );
        return $definition;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return Message::fromArray($this->rawData);
    }

    /**
     * @return string
     */
    public function getMessageType()
    {
        if (is_null($this->getPayloadNotIncluded())) {
            return $this->getMessage()->getType();
        }
        return $this->getPayloadNotIncluded()->getPayloadType();
    }
}
