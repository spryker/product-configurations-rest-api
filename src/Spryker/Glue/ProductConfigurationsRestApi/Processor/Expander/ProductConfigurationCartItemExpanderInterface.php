<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductConfigurationsRestApi\Processor\Expander;

use Generated\Shared\Transfer\CartItemRequestTransfer;
use Generated\Shared\Transfer\RestCartItemsAttributesTransfer;

interface ProductConfigurationCartItemExpanderInterface
{
    public function expandWithProductConfigurationInstance(
        CartItemRequestTransfer $cartItemRequestTransfer,
        RestCartItemsAttributesTransfer $restCartItemsAttributesTransfer
    ): CartItemRequestTransfer;
}
