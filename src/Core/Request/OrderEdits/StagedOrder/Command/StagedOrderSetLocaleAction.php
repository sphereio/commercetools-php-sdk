<?php
/**
 *
 */

namespace Commercetools\Core\Request\OrderEdits\StagedOrder\Command;

use Commercetools\Core\Request\Orders\Command\OrderSetLocaleAction;

/**
 * @package Commercetools\Core\Request\OrderEdits\StagedOrder\Command
 *
 * @method string getAction()
 * @method StagedOrderSetLocaleAction setAction(string $action = null)
 * @method string getLocale()
 */
class StagedOrderSetLocaleAction extends OrderSetLocaleAction implements StagedOrderUpdateAction
{
}
