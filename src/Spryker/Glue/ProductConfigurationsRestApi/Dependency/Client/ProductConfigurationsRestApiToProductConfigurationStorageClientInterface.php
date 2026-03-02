<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductConfigurationsRestApi\Dependency\Client;

use Generated\Shared\Transfer\ProductConfigurationInstanceCollectionTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceCriteriaTransfer;

interface ProductConfigurationsRestApiToProductConfigurationStorageClientInterface
{
    public function getProductConfigurationInstanceCollection(
        ProductConfigurationInstanceCriteriaTransfer $productConfigurationInstanceCriteriaTransfer
    ): ProductConfigurationInstanceCollectionTransfer;
}
