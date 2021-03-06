<?php
/**
 * @author @jenschude <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\IntegrationTests\Order;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Fixtures\FixtureException;
use Commercetools\Core\IntegrationTests\ApiTestCase;
use Commercetools\Core\IntegrationTests\Channel\ChannelFixture;
use Commercetools\Core\IntegrationTests\Customer\CustomerFixture;
use Commercetools\Core\IntegrationTests\Payment\PaymentFixture;
use Commercetools\Core\IntegrationTests\State\StateFixture;
use Commercetools\Core\IntegrationTests\Store\StoreFixture;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\CartState;
use Commercetools\Core\Model\Cart\ItemShippingDetailsDraft;
use Commercetools\Core\Model\Cart\ItemShippingTarget;
use Commercetools\Core\Model\Cart\ItemShippingTargetCollection;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Cart\ShippingInfo;
use Commercetools\Core\Model\Channel\Channel;
use Commercetools\Core\Model\Channel\ChannelDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\AddressCollection;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerDraft;
use Commercetools\Core\Model\Order\DeliveryItem;
use Commercetools\Core\Model\Order\DeliveryItemCollection;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderState;
use Commercetools\Core\Model\Order\Parcel;
use Commercetools\Core\Model\Order\ParcelCollection;
use Commercetools\Core\Model\Order\ParcelMeasurements;
use Commercetools\Core\Model\Order\PaymentState;
use Commercetools\Core\Model\Order\ReturnItem;
use Commercetools\Core\Model\Order\ReturnItemCollection;
use Commercetools\Core\Model\Order\ReturnPaymentState;
use Commercetools\Core\Model\Order\ReturnShipmentState;
use Commercetools\Core\Model\Order\ShipmentState;
use Commercetools\Core\Model\Order\TrackingData;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateDraft;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\Store\Store;
use Commercetools\Core\Model\Store\StoreReference;
use Commercetools\Core\Request\Carts\CartByIdGetRequest;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartDeleteRequest;
use Commercetools\Core\Request\Carts\CartReplicateRequest;
use Commercetools\Core\Request\InStores\InStoreRequestDecorator;
use Commercetools\Core\Request\Orders\Command\OrderAddDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderAddItemShippingAddressAction;
use Commercetools\Core\Request\Orders\Command\OrderAddParcelToDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddReturnInfoAction;
use Commercetools\Core\Request\Orders\Command\OrderChangeOrderStateAction;
use Commercetools\Core\Request\Orders\Command\OrderChangePaymentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderChangeShipmentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderRemoveDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderRemoveItemShippingAddressAction;
use Commercetools\Core\Request\Orders\Command\OrderRemoveParcelFromDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderRemovePaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderSetBillingAddress;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomerEmail;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomerIdAction;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomLineItemShippingDetailsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetDeliveryAddressAction;
use Commercetools\Core\Request\Orders\Command\OrderSetDeliveryItemsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetLineItemShippingDetailsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetLocaleAction;
use Commercetools\Core\Request\Orders\Command\OrderSetOrderNumberAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelItemsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelMeasurementsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelTrackingDataAction;
use Commercetools\Core\Request\Orders\Command\OrderSetReturnPaymentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderSetReturnShipmentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderSetShippingAddress;
use Commercetools\Core\Request\Orders\Command\OrderSetStoreAction;
use Commercetools\Core\Request\Orders\Command\OrderUpdateItemShippingAddressAction;
use Commercetools\Core\Request\Orders\Command\OrderUpdateSyncInfoAction;
use Commercetools\Core\Request\Orders\OrderCreateFromCartRequest;
use Commercetools\Core\Request\Orders\OrderDeleteRequest;

class OrderUpdateRequestTest extends ApiTestCase
{
//    todo cancel getCartDraft() and createOrder() after the OrderEdit migration
    /**
     * @return CartDraft
     */
    protected function getCartDraft()
    {
        $draft = CartDraft::ofCurrency('EUR')->setCountry('DE');
        /**
         * @var Customer $customer
         */
        $customer = $this->getCustomer();
        $draft->setCustomerId($customer->getId())
            ->setShippingAddress($customer->getDefaultShippingAddress())
            ->setBillingAddress($customer->getDefaultBillingAddress())
            ->setCustomerEmail($customer->getEmail())
            ->setLineItems(
                LineItemDraftCollection::of()
                    ->add(
                        LineItemDraft::ofProductIdVariantIdAndQuantity($this->getProduct()->getId(), 1, 1)
                    )
            )
            ->setShippingMethod($this->getShippingMethod()->getReference());

        return $draft;
    }

    protected function createOrder(
        CartDraft $draft,
        $orderNumber = null,
        $paymentState = null,
        $orderState = null,
        $state = null,
        $shipmentState = null
    ) {
        $request = CartCreateRequest::ofDraft($draft);
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->cleanupRequests[] = $cartDeleteRequest = CartDeleteRequest::ofIdAndVersion(
            $cart->getId(),
            $cart->getVersion()
        );

        $orderRequest = OrderCreateFromCartRequest::ofCartIdAndVersion($cart->getId(), $cart->getVersion());
        if (!is_null($orderNumber)) {
            $orderRequest->setOrderNumber($orderNumber);
        }
        if (!is_null($paymentState)) {
            $orderRequest->setPaymentState($paymentState);
        }
        if (!is_null($orderState)) {
            $orderRequest->setOrderState($orderState);
        }
        if (!is_null($state)) {
            $orderRequest->setState($state);
        }
        if (!is_null($shipmentState)) {
            $orderRequest->setShipmentState($shipmentState);
        }
        $response = $orderRequest->executeWithClient($this->getClient());
        $order = $orderRequest->mapResponse($response);
        $this->cleanupRequests[] = $this->deleteRequest = OrderDeleteRequest::ofIdAndVersion(
            $order->getId(),
            $order->getVersion()
        );

        $request = CartByIdGetRequest::ofId($cart->getId());
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $cartDeleteRequest->setVersion($cart->getVersion());

        return $order;
    }

    public function testOrderByOrderNumber()
    {
        $client = $this->getApiClient();
        $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

        OrderFixture::withUpdateableDraftOrder(
            $client,
            function (OrderCreateFromCartRequest $request) use ($orderNumber) {
                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order) use ($client, $orderNumber) {
                $request = RequestBuilder::of()->orders()->getByOrderNumber($orderNumber);
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);

                return $result;
            }
        );
    }

    public function testUpdateOrderByOrderNumber()
    {
        $client = $this->getApiClient();
        $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

        OrderFixture::withUpdateableDraftOrder(
            $client,
            function (OrderCreateFromCartRequest $request) use ($orderNumber) {
                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order, Customer $customer, Product $product) use ($client, $orderNumber) {
                $this->assertSame(
                    $product->getProductType()->getId(),
                    $order->getLineItems()->current()->getProductType()->getId()
                );
                $request = RequestBuilder::of()->orders()->updateByOrderNumber($order)
                    ->addAction(OrderChangeOrderStateAction::ofOrderState(OrderState::COMPLETE));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(OrderState::COMPLETE, $result->getOrderState());

                return $result;
            }
        );
    }

    public function testDeleteOrderByOrderNumber()
    {
        $client = $this->getApiClient();
        $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

        OrderFixture::withUpdateableDraftOrder(
            $client,
            function (OrderCreateFromCartRequest $request) use ($orderNumber) {
                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order) use ($client, $orderNumber) {
                $request = RequestBuilder::of()->orders()->deleteByOrderNumber($order);
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);

                return $result;
            }
        );
    }


    public function testChangeState()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order, Customer $customer, Product $product) use ($client) {
                $this->assertSame(
                    $product->getProductType()->getId(),
                    $order->getLineItems()->current()->getProductType()->getId()
                );
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderChangeOrderStateAction::ofOrderState(OrderState::COMPLETE));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(OrderState::COMPLETE, $result->getOrderState());

                return $result;
            }
        );
    }

    public function testChangeShipmentState()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderChangeShipmentStateAction::ofShipmentState(ShipmentState::SHIPPED));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(ShipmentState::SHIPPED, $result->getShipmentState());

                return $result;
            }
        );
    }

    public function testChangePaymentState()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderChangePaymentStateAction::ofPaymentState(PaymentState::PAID));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(PaymentState::PAID, $result->getPaymentState());

                return $result;
            }
        );
    }

    public function testUpdateSyncInfo()
    {
        $client = $this->getApiClient();

        ChannelFixture::withDraftChannel(
            $client,
            function (ChannelDraft $channelDraft) {
                return $channelDraft->setRoles(['OrderExport']);
            },
            function (Channel $channel) use ($client) {
                OrderFixture::withUpdateableOrder(
                    $client,
                    function (Order $order) use ($client, $channel) {
                        $syncedAt = new \DateTime();
                        $request = RequestBuilder::of()->orders()->update($order)
                            ->addAction(OrderUpdateSyncInfoAction::ofChannel($channel->getReference())->setSyncedAt($syncedAt));
                        $response = $this->execute($client, $request);
                        $result = $request->mapFromResponse($response);

                        $this->assertNotSame($order->getVersion(), $result->getVersion());
                        $this->assertInstanceOf(Order::class, $result);
                        $this->assertSame($channel->getId(), $result->getSyncInfo()->current()->getChannel()->getId());
                        $syncedAt->setTimezone(new \DateTimeZone('UTC'));
                        $this->assertSame($syncedAt->format('c'), $result->getSyncInfo()->current()->getSyncedAt()->getDateTime()->format('c'));

                        return $result;
                    }
                );
            }
        );
    }

    public function testReturnInfo()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddReturnInfoAction::of()->setItems(
                            ReturnItemCollection::of()->add(
                                ReturnItem::of()
                                    ->setQuantity(1)
                                    ->setLineItemId($lineItem->getId())
                                    ->setShipmentState(ReturnShipmentState::RETURNED)
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(
                    ReturnShipmentState::RETURNED,
                    $result->getReturnInfo()->current()->getItems()->current()->getShipmentState()
                );
                $returnItem = $result->getReturnInfo()->current()->getItems()->current();
                $this->assertSame(
                    $lineItem->getId(),
                    $returnItem->getLineItemId()
                );
                $order = $result;
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetReturnShipmentStateAction::ofReturnItemIdAndShipmentState(
                            $returnItem->getId(),
                            ReturnShipmentState::BACK_IN_STOCK
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $returnItem = $result->getReturnInfo()->current()->getItems()->current();
                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(
                    ReturnShipmentState::BACK_IN_STOCK,
                    $returnItem->getShipmentState()
                );
                $order = $result;
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetReturnPaymentStateAction::ofReturnItemIdAndPaymentState(
                            $returnItem->getId(),
                            ReturnPaymentState::REFUNDED
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $returnItem = $result->getReturnInfo()->current()->getItems()->current();
                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame(
                    ReturnPaymentState::REFUNDED,
                    $returnItem->getPaymentState()
                );

                return $result;
            }
        );
    }

    public function testAddDelivery()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::ofDeliveryItems(
                            DeliveryItemCollection::of()->add(
                                DeliveryItem::of()->setId($lineItem->getId())->setQuantity(1)
                            )
                        )->setAddress(Address::of()->setCountry('DE'))
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);

                $shippingInfo = $result->getShippingInfo();
                $this->assertInstanceOf(ShippingInfo::class, $shippingInfo);

                $delivery = $shippingInfo->getDeliveries()->current();
                $this->assertSame($lineItem->getId(), $delivery->getItems()->current()->getId());
                $order = $result;

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddParcelToDeliveryAction::ofDeliveryId($delivery->getId())
                            ->setMeasurements(
                                ParcelMeasurements::of()
                                    ->setHeightInMillimeter(100)
                                    ->setLengthInMillimeter(100)
                                    ->setWidthInMillimeter(100)
                                    ->setWeightInGram(100)
                            )
                            ->setTrackingData(
                                TrackingData::of()
                                    ->setTrackingId('123456')
                                    ->setCarrier('DHL')
                                    ->setProvider('Schenker')
                                    ->setProviderTransaction('abcdef')
                                    ->setIsReturn(false)
                            )
                            ->setItems(
                                DeliveryItemCollection::of()->add(
                                    DeliveryItem::of()->setId($lineItem->getId())->setQuantity(3)
                                )
                            )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $delivery = $result->getShippingInfo()->getDeliveries()->current();
                $this->assertSame('DE', $delivery->getAddress()->getCountry());
                $this->assertSame(100, $delivery->getParcels()->current()->getMeasurements()->getHeightInMillimeter());
                $this->assertSame('DHL', $delivery->getParcels()->current()->getTrackingData()->getCarrier());

                return $result;
            }
        );
    }

    public function testDeliverySetAddress()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::ofDeliveryItems(
                            DeliveryItemCollection::of()->add(
                                DeliveryItem::of()->setId($lineItem->getId())->setQuantity(1)
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $delivery = $result->getShippingInfo()->getDeliveries()->current();
                $this->assertSame($lineItem->getId(), $delivery->getItems()->current()->getId());
                $order = $result;

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetDeliveryAddressAction::ofDeliveryId($delivery->getId())->setAddress(
                            Address::of()->setCountry('DE')
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $delivery = $result->getShippingInfo()->getDeliveries()->current();
                $this->assertSame('DE', $delivery->getAddress()->getCountry());

                return $result;
            }
        );
    }

    public function testRemoveDelivery()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::ofDeliveryItems(
                            DeliveryItemCollection::of()->add(
                                DeliveryItem::of()->setId($lineItem->getId())->setQuantity(1)
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $delivery = $result->getShippingInfo()->getDeliveries()->current();
                $this->assertSame($lineItem->getId(), $delivery->getItems()->current()->getId());
                $order = $result;

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderRemoveDeliveryAction::ofDelivery($delivery->getId())
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertCount(0, $result->getShippingInfo()->getDeliveries());

                return $result;
            }
        );
    }

    public function testSetDeliveryItems()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $order = $result;

                $delivery = $order->getShippingInfo()->getDeliveries()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetDeliveryItemsAction::ofDeliveryAndItems(
                            $delivery->getId(),
                            DeliveryItemCollection::of()->add(
                                DeliveryItem::of()->setId($lineItem->getId())->setQuantity(1)
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $delivery = $result->getShippingInfo()->getDeliveries()->current();
                $this->assertSame($lineItem->getId(), $delivery->getItems()->current()->getId());

                return $result;
            }
        );
    }

    public function testSetOrderNumber()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $orderNumber = OrderFixture::uniqueOrderString() . '-order';

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetOrderNumberAction::of()->setOrderNumber($orderNumber));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame($orderNumber, $result->getOrderNumber());

                return $result;
            }
        );
    }

    public function testPayment()
    {
        $client = $this->getApiClient();

        PaymentFixture::withPayment(
            $client,
            function (Payment $payment) use ($client) {
                OrderFixture::withUpdateableOrder(
                    $client,
                    function (Order $order) use ($client, $payment) {
                        $request = RequestBuilder::of()->orders()->update($order)
                            ->addAction(OrderAddPaymentAction::of()->setPayment($payment->getReference()));
                        $response = $this->execute($client, $request);
                        $result = $request->mapFromResponse($response);

                        $this->assertSame($payment->getId(), $result->getPaymentInfo()->getPayments()->current()->getId());

                        $request = RequestBuilder::of()->orders()->update($result)
                            ->addAction(OrderRemovePaymentAction::of()->setPayment($payment->getReference()));
                        $response = $this->execute($client, $request);
                        $result = $request->mapFromResponse($response);

                        $this->assertNull($result->getPaymentInfo());

                        return $result;
                    }
                );
            }
        );
    }

    public function localeProvider()
    {
        return [
            ['en', 'en'],
            ['de', 'de'],
            ['de-de', 'de-DE'],
            ['de-DE', 'de-DE'],
            ['de_de', 'de-DE'],
            ['de_DE', 'de-DE'],
        ];
    }

    /**
     * @dataProvider localeProvider
     */
    public function testLocale($locale, $expectedLocale)
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client, $locale, $expectedLocale) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetLocaleAction::ofLocale($locale));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertSame($expectedLocale, $result->getLocale());

                return $result;
            }
        );
    }

    public function invalidLocaleProvider()
    {
        return [
            ['en-en'],
            ['en_en'],
            ['en_EN'],
            ['en-EN'],
            ['fr'],
        ];
    }

    /**
     * @dataProvider invalidLocaleProvider
     */
    public function testInvalidLocale($locale)
    {
        $this->expectException(FixtureException::class);
        $this->expectExceptionCode(400);
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client, $locale) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetLocaleAction::ofLocale($locale));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                return $result;
            }
        );
    }

    public function testSetCustomerEmail()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetCustomerEmail::of()->setEmail(OrderFixture::uniqueOrderString() . '-new@example.com'));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);
                $this->assertNotSame($order->getCustomerEmail(), $result->getCustomerEmail());

                return $result;
            }
        );
    }

    public function testSetCustomerId()
    {
        $client = $this->getApiClient();

        CustomerFixture::withDraftCustomer(
            $client,
            function (CustomerDraft $customerDraft) {
                $customerDraft = CustomerDraft::ofEmailNameAndPassword(
                    CustomerFixture::uniqueCustomerString() . '-another@example.com',
                    'firstName',
                    'lastName',
                    'password'
                );

                return $customerDraft;
            },
            function (Customer $customer) use ($client) {
                OrderFixture::withUpdateableOrder(
                    $client,
                    function (Order $order) use ($client, $customer) {
                        $this->assertNotSame($order->getCustomerId(), $customer->getId());

                        $request = RequestBuilder::of()->orders()->update($order)
                            ->addAction(OrderSetCustomerIdAction::of()->setCustomerId($customer->getId()));
                        $response = $this->execute($client, $request);
                        $result = $request->mapFromResponse($response);

                        $this->assertInstanceOf(Order::class, $result);
                        $this->assertNotSame($order->getCustomerId(), $result->getCustomerId());
                        $this->assertSame($customer->getId(), $result->getCustomerId());

                        return $result;
                    }
                );
            }
        );
    }

    public function testSetShippingAddress()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetShippingAddress::of()->setAddress(
                        Address::of()->setCountry('DE')->setFirstName(OrderFixture::uniqueOrderString() . '-new')
                    ));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);
                $this->assertNotSame($order->getShippingAddress()->getFirstName(), $result->getShippingAddress()->getFirstName());

                return $result;
            }
        );
    }

    public function testSetBillingAddress()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableOrder(
            $client,
            function (Order $order) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderSetBillingAddress::of()->setAddress(
                        Address::of()->setCountry('DE')->setFirstName($this->getTestRun() . '-new')
                    ));
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);
                $this->assertNotSame($order->getBillingAddress()->getFirstName(), $result->getBillingAddress()->getFirstName());

                return $result;
            }
        );
    }

    public function testAddDeliveryWithParcel()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartOrderAddingTwoProducts(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->getAt(0);
                $lineItem2 = $order->getLineItems()->getAt(1);
                $customLineItem = $order->getCustomLineItems()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()
                            ->setParcels(
                                ParcelCollection::of()->add(
                                    Parcel::of()->setItems(
                                        DeliveryItemCollection::of()->add(
                                            DeliveryItem::of()
                                                ->setId($lineItem->getId())
                                                ->setQuantity(2)
                                        )->add(
                                            DeliveryItem::of()
                                                ->setId($customLineItem->getId())
                                                ->setQuantity(1)
                                        )->add(
                                            DeliveryItem::of()
                                                ->setId($lineItem2->getId())
                                                ->setQuantity(10)
                                        )
                                    )
                                )
                            )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertCount(
                    3,
                    $result->getShippingInfo()->getDeliveries()->current()->getParcels()->current()->getItems()
                );

                return $result;
            }
        );
    }

    public function testSetParcelItems()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartOrderAddingTwoProducts(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->getAt(0);

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()->setParcels(
                            ParcelCollection::of()->add(
                                Parcel::of()->setItems(
                                    DeliveryItemCollection::of()->add(
                                        DeliveryItem::of()
                                            ->setId($lineItem->getId())
                                            ->setQuantity(2)
                                    )
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $order = $request->mapFromResponse($response);

                $lineItem = $order->getLineItems()->getAt(0);
                $lineItem2 = $order->getLineItems()->getAt(1);
                $customLineItem = $order->getCustomLineItems()->current();
                $parcel = $order->getShippingInfo()->getDeliveries()->current()->getParcels()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetParcelItemsAction::ofParcel($parcel->getId())->setItems(
                            DeliveryItemCollection::of()->add(
                                DeliveryItem::of()
                                    ->setId($lineItem->getId())
                                    ->setQuantity(2)
                            )->add(
                                DeliveryItem::of()
                                    ->setId($customLineItem->getId())
                                    ->setQuantity(1)
                            )->add(
                                DeliveryItem::of()
                                    ->setId($lineItem2->getId())
                                    ->setQuantity(10)
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertCount(
                    3,
                    $result->getShippingInfo()->getDeliveries()->current()->getParcels()->current()->getItems()
                );

                return $result;
            }
        );
    }

    public function testSetParcelMeasurements()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartOrderAddingTwoProducts(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->getAt(0);

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()->setParcels(
                            ParcelCollection::of()->add(
                                Parcel::of()->setItems(
                                    DeliveryItemCollection::of()->add(
                                        DeliveryItem::of()
                                            ->setId($lineItem->getId())
                                            ->setQuantity(2)
                                    )
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $order = $request->mapFromResponse($response);

                $parcel = $order->getShippingInfo()->getDeliveries()->current()->getParcels()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetParcelMeasurementsAction::ofParcel($parcel->getId())->setMeasurements(
                            ParcelMeasurements::of()
                                ->setWeightInGram(10)
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertSame(
                    10,
                    $result->getShippingInfo()->getDeliveries()->current()->getParcels()->current()->getMeasurements()->getWeightInGram()
                );

                return $result;
            }
        );
    }

    public function testSetTrackingData()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartOrderAddingTwoProducts(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->getAt(0);

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()->setParcels(
                            ParcelCollection::of()->add(
                                Parcel::of()->setItems(
                                    DeliveryItemCollection::of()->add(
                                        DeliveryItem::of()
                                            ->setId($lineItem->getId())
                                            ->setQuantity(2)
                                    )
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $order = $request->mapFromResponse($response);

                $parcel = $order->getShippingInfo()->getDeliveries()->current()->getParcels()->current();
                $trackingId = uniqid();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetParcelTrackingDataAction::ofParcel($parcel->getId())->setTrackingData(
                            TrackingData::of()->setTrackingId($trackingId)
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertSame(
                    $trackingId,
                    $result->getShippingInfo()->getDeliveries()->current()->getParcels()->current()->getTrackingData()->getTrackingId()
                );

                return $result;
            }
        );
    }

    public function testRemoveParcel()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartOrderAddingTwoProducts(
            $client,
            function (Order $order) use ($client) {
                $lineItem = $order->getLineItems()->getAt(0);

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddDeliveryAction::of()->setParcels(
                            ParcelCollection::of()->add(
                                Parcel::of()->setItems(
                                    DeliveryItemCollection::of()->add(
                                        DeliveryItem::of()
                                            ->setId($lineItem->getId())
                                            ->setQuantity(2)
                                    )
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $order = $request->mapFromResponse($response);

                $parcel = $order->getShippingInfo()->getDeliveries()->current()->getParcels()->current();

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderRemoveParcelFromDeliveryAction::ofParcel($parcel->getId())
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertCount(
                    0,
                    $result->getShippingInfo()->getDeliveries()->current()->getParcels()
                );

                return $result;
            }
        );
    }

    public function testCreateReplicaCartFromOrder()
    {
        $cartDraft = $this->getCartDraft();
        $order = $this->createOrder($cartDraft);

        $request = CartReplicateRequest::ofOrderId($order->getId());

        $response = $request->executeWithClient($this->getClient());
        $replicaCart = $request->mapResponse($response);
        $this->cleanupRequests[] = CartDeleteRequest::ofIdAndVersion($replicaCart->getId(), $replicaCart->getVersion());

        $this->assertNotEmpty($replicaCart->getLineItems());

        $orderLineItem = $order->getLineItems()->current()->getProductId();
        $replicaCartLineItem = $replicaCart->getLineItems()->current()->getProductId();

        $this->assertSame($orderLineItem, $replicaCartLineItem);
        $this->assertNotNull($replicaCartLineItem);
        $this->assertSame(CartState::ACTIVE, $replicaCart->getCartState());
    }

    public function testCreateOrderWithInitialData()
    {
        $client = $this->getApiClient();
        $stateType = 'OrderState';
        $orderNumber = '123';
        $paymentState = 'Pending';
        $orderState = 'Complete';
        $shipmentState = 'Delayed';

        StateFixture::withDraftState(
            $client,
            function (StateDraft $state2Draft) use ($stateType) {
                return $state2Draft->setType($stateType);
            },
            function (State $state) use ($client, $orderNumber, $paymentState, $orderState, $shipmentState) {
                OrderFixture::withUpdateableDraftOrder(
                    $client,
                    function (OrderCreateFromCartRequest $request) use ($state, $orderNumber, $paymentState, $orderState, $shipmentState) {
                        $stateReference = $state->getReference();

                        return $request->setOrderNumber($orderNumber)
                            ->setPaymentState($paymentState)->setOrderState($orderState)
                            ->setState($stateReference)->setShipmentState($shipmentState);
                    },
                    function (Order $order) use ($client, $state, $orderNumber, $paymentState, $orderState, $shipmentState) {
                        $stateReference = $state->getReference();

                        $this->assertSame($orderNumber, $order->getOrderNumber());
                        $this->assertSame($paymentState, $order->getPaymentState());
                        $this->assertSame($orderState, $order->getOrderState());
                        $this->assertInstanceOf(StateReference::class, $order->getState());
                        $this->assertSame($stateReference->getId(), $order->getState()->getId());
                        $this->assertSame($shipmentState, $order->getShipmentState());

                        return $order;
                    }
                );
            }
        );
    }

    public function testItemShippingAddress()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableDraftOrder(
            $client,
            function (OrderCreateFromCartRequest $request) {
                $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderAddItemShippingAddressAction::of()->setAddress(
                            Address::of()->setCountry('DE')->setKey('key1')
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertNotSame($order->getVersion(), $result->getVersion());
                $this->assertInstanceOf(Order::class, $result);

                $itemShippingAddresses = $result->getItemShippingAddresses();

                $this->assertInstanceOf(AddressCollection::class, $itemShippingAddresses);
                $this->assertSame('DE', $itemShippingAddresses->current()->getCountry());
                $this->assertSame('key1', $itemShippingAddresses->current()->getKey());

                $request = RequestBuilder::of()->orders()->update($result)
                    ->addAction(
                        OrderUpdateItemShippingAddressAction::of()->setAddress(
                            Address::of()->setCountry('US')->setKey('key1')
                        )
                    )->addAction(
                        OrderAddItemShippingAddressAction::of()->setAddress(
                            Address::of()->setCountry('FR')->setKey('key2')
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);

                $itemShippingAddresses = $result->getItemShippingAddresses();

                $this->assertInstanceOf(AddressCollection::class, $itemShippingAddresses);
                $this->assertSame(2, $itemShippingAddresses->count());
                $this->assertSame('US', $itemShippingAddresses->current()->getCountry());
                $this->assertSame('key1', $itemShippingAddresses->current()->getKey());
                $this->assertSame('FR', $itemShippingAddresses->getAt(1)->getCountry());
                $this->assertSame('key2', $itemShippingAddresses->getAt(1)->getKey());

                $request = RequestBuilder::of()->orders()->update($result)
                    ->addAction(
                        OrderRemoveItemShippingAddressAction::of()->setAddressKey('key1')
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);

                $itemShippingAddresses = $result->getItemShippingAddresses();

                $this->assertInstanceOf(AddressCollection::class, $itemShippingAddresses);
                $this->assertSame(1, $itemShippingAddresses->count());
                $this->assertSame('FR', $itemShippingAddresses->getAt(0)->getCountry());
                $this->assertSame('key2', $itemShippingAddresses->getAt(0)->getKey());

                return $result;
            }
        );
    }

    public function testLineItemShippingDetails()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartDraftOrderSetting(
            $client,
            function (OrderCreateFromCartRequest $request) {
                $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order) use ($client) {
                $this->assertNull($order->getLineItems()->current()->getShippingDetails());

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetLineItemShippingDetailsAction::ofLineItemIdAndShippingDetails(
                            $order->getLineItems()->current()->getId(),
                            ItemShippingDetailsDraft::ofTargets(
                                ItemShippingTargetCollection::of()->add(
                                    ItemShippingTarget::of()->setQuantity(10)->setAddressKey('key1')
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertSame(
                    'key1',
                    $result->getLineItems()->current()->getShippingDetails()->getTargets()->current()->getAddressKey()
                );
                $this->assertSame(
                    10,
                    $result->getLineItems()->current()->getShippingDetails()->getTargets()->current()->getQuantity()
                );

                return $result;
            }
        );
    }

    public function testCustomLineItemShippingDetails()
    {
        $client = $this->getApiClient();

        OrderFixture::withUpdateableCartDraftOrderSetting(
            $client,
            function (OrderCreateFromCartRequest $request) {
                $orderNumber = (new \DateTime())->format('Y/m/d') . ' ' . OrderFixture::uniqueOrderString();

                return $request->setOrderNumber($orderNumber);
            },
            function (Order $order) use ($client) {
                $this->assertNull($order->getCustomLineItems()->current()->getShippingDetails());

                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(
                        OrderSetCustomLineItemShippingDetailsAction::ofCustomLineItemIdAndShippingDetails(
                            $order->getCustomLineItems()->current()->getId(),
                            ItemShippingDetailsDraft::ofTargets(
                                ItemShippingTargetCollection::of()->add(
                                    ItemShippingTarget::of()->setQuantity(10)->setAddressKey('key1')
                                )
                            )
                        )
                    );
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertSame(
                    'key1',
                    $result->getCustomLineItems()->current()->getShippingDetails()->getTargets()->current()->getAddressKey()
                );
                $this->assertSame(
                    10,
                    $result->getCustomLineItems()->current()->getShippingDetails()->getTargets()->current()->getQuantity()
                );

                return $result;
            }
        );
    }

    public function testUpdateAndDeleteForOrderInStore()
    {
        $client = $this->getApiClient();

        OrderFixture::withStoreCartDraftOrder(
            $client,
            function (CartDraft $draft) {
                return $draft;
            },
            function (OrderCreateFromCartRequest $request) {
                return $request;
            },
            function (Order $order, Store $store) use ($client) {
                $request = RequestBuilder::of()->orders()->update($order)
                    ->addAction(OrderChangeShipmentStateAction::ofShipmentState(ShipmentState::SHIPPED))
                    ->inStore($store->getKey());
                $response = $this->execute($client, $request);
                $result = $request->mapFromResponse($response);

                $this->assertInstanceOf(Order::class, $result);
                $this->assertStringStartsWith('in-store/key='.$store->getKey().'/orders/'.$result->getId(), (string)$request->httpRequest()->getUri());
                $this->assertSame(ShipmentState::SHIPPED, $result->getShipmentState());
                $this->assertNotSame($order->getVersion(), $result->getVersion());

                $orderRequest = RequestBuilder::of()->orders()->delete($result);
                $request = InStoreRequestDecorator::ofStoreKeyAndRequest($store->getKey(), $orderRequest);
                $response = $request->executeWithClient($this->getClient());
                $result = $request->mapResponse($response);

                $this->assertInstanceOf(Order::class, $result);
                $this->assertSame($order->getId(), $result->getId());

                return $result;
            }
        );
    }

    public function testSetStore()
    {
        $client = $this->getApiClient();

        StoreFixture::withStore(
            $client,
            function (Store $store) use ($client) {
                OrderFixture::withUpdateableOrder(
                    $client,
                    function (Order $order) use ($client, $store) {
                        $request = RequestBuilder::of()->orders()->update($order)
                            ->addAction(OrderSetStoreAction::of()->setStore(StoreReference::ofId($store->getId())));
                        $response = $this->execute($client, $request);
                        $result = $request->mapFromResponse($response);

                        $this->assertInstanceOf(Order::class, $result);
                        $this->assertNotSame($order->getVersion(), $result->getVersion());
                        $this->assertSame($store->getKey(), $result->getStore()->getKey());

                        return $result;
                    }
                );
            }
        );
    }
}
