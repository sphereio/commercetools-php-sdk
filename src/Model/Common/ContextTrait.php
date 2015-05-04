<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Sphere\Core\Model\Common;


use Sphere\Core\Error\InvalidArgumentException;

trait ContextTrait
{
    /**
     * @var Context|callable
     */
    protected $context;

    /**
     * @return Context
     */
    public function getContext()
    {
        if (is_null($this->context)) {
            $this->context = new Context();
        }
        if (is_callable($this->context)) {
            return call_user_func($this->context);
        }
        return $this->context;
    }

    /**
     * @return callable
     */
    public function getContextCallback()
    {
        return [$this, 'getContext'];
    }

    /**
     * @param Context|callable $context
     * @return $this
     */
    public function setContext($context = null)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param Context $context
     * @return $this
     */
    public function setContextIfNull($context = null)
    {
        if (is_null($this->context)) {
            $this->setContext($context);
        }

        return $this;
    }
}