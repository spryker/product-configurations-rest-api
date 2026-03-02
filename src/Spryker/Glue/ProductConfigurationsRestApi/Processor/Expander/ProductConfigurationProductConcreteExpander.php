<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductConfigurationsRestApi\Processor\Expander;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceCollectionTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceConditionsTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceCriteriaTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceTransfer;
use Generated\Shared\Transfer\RestProductConfigurationInstanceAttributesTransfer;
use Spryker\Glue\ProductConfigurationsRestApi\Dependency\Client\ProductConfigurationsRestApiToProductConfigurationStorageClientInterface;

class ProductConfigurationProductConcreteExpander implements ProductConfigurationProductConcreteExpanderInterface
{
    /**
     * @var \Spryker\Glue\ProductConfigurationsRestApi\Dependency\Client\ProductConfigurationsRestApiToProductConfigurationStorageClientInterface
     */
    protected ProductConfigurationsRestApiToProductConfigurationStorageClientInterface $productConfigurationStorageClient;

    public function __construct(ProductConfigurationsRestApiToProductConfigurationStorageClientInterface $productConfigurationStorageClient)
    {
        $this->productConfigurationStorageClient = $productConfigurationStorageClient;
    }

    public function expandWithProductConfigurationInstance(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer
    ): ConcreteProductsRestAttributesTransfer {
        $productConfigurationInstanceTransfer = $this->findProductConfigurationInstance($concreteProductsRestAttributesTransfer);

        if (!$productConfigurationInstanceTransfer) {
            return $concreteProductsRestAttributesTransfer;
        }

        $restProductConfigurationInstanceAttributesTransfer = (new RestProductConfigurationInstanceAttributesTransfer())->fromArray(
            $productConfigurationInstanceTransfer->toArray(),
            true,
        );

        return $concreteProductsRestAttributesTransfer->setProductConfigurationInstance(
            $restProductConfigurationInstanceAttributesTransfer,
        );
    }

    protected function findProductConfigurationInstance(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer
    ): ?ProductConfigurationInstanceTransfer {
        $productConfigurationInstanceCollectionTransfer = $this->getProductConfigurationInstanceCollection($concreteProductsRestAttributesTransfer);

        if (!$productConfigurationInstanceCollectionTransfer->getProductConfigurationInstances()->count()) {
            return null;
        }

        return $productConfigurationInstanceCollectionTransfer->getProductConfigurationInstances()
            ->getIterator()
            ->current();
    }

    protected function getProductConfigurationInstanceCollection(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer
    ): ProductConfigurationInstanceCollectionTransfer {
        $productConfigurationInstanceConditionsTransfer = (new ProductConfigurationInstanceConditionsTransfer())
            ->addSku($concreteProductsRestAttributesTransfer->getSkuOrFail());

        $productConfigurationInstanceCriteriaTransfer = (new ProductConfigurationInstanceCriteriaTransfer())
            ->setProductConfigurationInstanceConditions($productConfigurationInstanceConditionsTransfer);

        return $this->productConfigurationStorageClient
            ->getProductConfigurationInstanceCollection($productConfigurationInstanceCriteriaTransfer);
    }
}
