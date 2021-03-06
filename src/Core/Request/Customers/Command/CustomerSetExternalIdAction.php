<?php
/**
 * @author @jenschude <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Request\Customers\Command;

use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Request\AbstractAction;

/**
 * @package Commercetools\Core\Request\Customers\Command
 * @link https://docs.commercetools.com/http-api-projects-customers.html#set-external-id
 * @method string getExternalId()
 * @method CustomerSetExternalIdAction setExternalId(string $externalId = null)
 * @method string getAction()
 * @method CustomerSetExternalIdAction setAction(string $action = null)
 */
class CustomerSetExternalIdAction extends AbstractAction
{
    public function fieldDefinitions()
    {
        return [
            'action' => [static::TYPE => 'string'],
            'externalId' => [static::TYPE => 'string'],
        ];
    }

    /**
     * @param array $data
     * @param Context|callable $context
     */
    public function __construct(array $data = [], $context = null)
    {
        parent::__construct($data, $context);
        $this->setAction('setExternalId');
    }
}
