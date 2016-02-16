<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Core\TaxCategory;


use Commercetools\Core\ApiTestCase;
use Commercetools\Core\Model\TaxCategory\TaxCategoryDraft;
use Commercetools\Core\Model\TaxCategory\TaxRate;
use Commercetools\Core\Model\TaxCategory\TaxRateCollection;
use Commercetools\Core\Request\TaxCategories\TaxCategoryByIdGetRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryCreateRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryDeleteRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;

class TaxCategoryQueryRequestTest extends ApiTestCase
{
    private $state;

    private function getState()
    {
        if (is_null($this->state)) {
            $this->state = (string)mt_rand(1, 1000);
        }

        return $this->state;
    }

    /**
     * @return TaxCategoryDraft
     */
    protected function getDraft()
    {
        $draft = TaxCategoryDraft::ofNameAndRates(
            'test-' . $this->getTestRun() . '-name',
            TaxRateCollection::of()->add(
                TaxRate::of()->setName('test-' . $this->getTestRun() . '-name')
                    ->setAmount(0.2)
                    ->setIncludedInPrice(true)
                    ->setCountry('DE')
                    ->setState($this->getState())
            )
        );

        return $draft;
    }

    protected function createTaxCategory(TaxCategoryDraft $draft)
    {
        $request = TaxCategoryCreateRequest::ofDraft($draft);
        $response = $request->executeWithClient($this->getClient());
        $taxCategory = $request->mapResponse($response);

        $this->cleanupRequests[] = TaxCategoryDeleteRequest::ofIdAndVersion(
            $taxCategory->getId(),
            $taxCategory->getVersion()
        );

        return $taxCategory;
    }

    public function testQueryByName()
    {
        $draft = $this->getDraft();
        $taxCategory = $this->createTaxCategory($draft);

        $result = $this->getClient()->execute(
            TaxCategoryQueryRequest::of()->where('name="' . $draft->getName() . '"')
        )->toObject();

        $this->assertCount(1, $result);
        $this->assertInstanceOf('\Commercetools\Core\Model\TaxCategory\TaxCategory', $result->getAt(0));
        $this->assertSame($taxCategory->getId(), $result->getAt(0)->getId());
    }

    public function testQueryById()
    {
        $draft = $this->getDraft();
        $taxCategory = $this->createTaxCategory($draft);

        $result = $this->getClient()->execute(TaxCategoryByIdGetRequest::ofId($taxCategory->getId()))->toObject();

        $this->assertInstanceOf('\Commercetools\Core\Model\TaxCategory\TaxCategory', $taxCategory);
        $this->assertSame($taxCategory->getId(), $result->getId());

    }
}
