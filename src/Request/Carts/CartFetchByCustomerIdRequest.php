<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Sphere\Core\Request\Carts;


use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Sphere\Core\Client\HttpMethod;
use Sphere\Core\Model\Common\Context;
use Sphere\Core\Request\AbstractApiRequest;
use Sphere\Core\Request\CustomerIdTrait;
use Sphere\Core\Response\SingleResourceResponse;

/**
 * Class CartFetchByCustomerIdRequest
 * @package Sphere\Core\Request\Carts
 * @link http://dev.sphere.io/http-api-projects-carts.html#cart-by-customer-id
 */
class CartFetchByCustomerIdRequest extends AbstractApiRequest
{
    use CustomerIdTrait;

    protected $resultClass = '\Sphere\Core\Model\Cart\Cart';

    /**
     * @param string $customerId
     * @param Context $context
     */
    public function __construct($customerId, Context $context = null)
    {
        parent::__construct(CartsEndpoint::endpoint(), $context);
        $this->byCustomerId($customerId);
    }

    /**
     * @return Request
     * @internal
     */
    public function httpRequest()
    {
        return new Request(HttpMethod::GET, $this->getPath());
    }

    /**
     * @param ResponseInterface $response
     * @return SingleResourceResponse
     */
    public function buildResponse(ResponseInterface $response)
    {
        return new SingleResourceResponse($response, $this, $this->getContext());
    }
}