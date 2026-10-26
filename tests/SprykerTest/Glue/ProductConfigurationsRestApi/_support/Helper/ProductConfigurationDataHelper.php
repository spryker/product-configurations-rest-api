<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Glue\ProductConfigurationsRestApi\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductConfigurationTransfer;
use Generated\Shared\Transfer\RestCartItemProductConfigurationInstanceAttributesTransfer;
use Generated\Shared\Transfer\RestCartItemsAttributesTransfer;
use Generated\Shared\Transfer\RestCurrencyTransfer;
use Generated\Shared\Transfer\RestProductConfigurationPriceAttributesTransfer;
use Orm\Zed\ProductConfigurationStorage\Persistence\SpyProductConfigurationStorageQuery;
use PHPUnit\Framework\Assert;
use SprykerTest\Client\Storage\Helper\StorageHelper;
use SprykerTest\Shared\ProductConfiguration\Helper\ProductConfigurationHelper;
use SprykerTest\Shared\Store\Helper\StoreDataHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * A product whose configuration is published, and the `productConfigurationInstance` payload the
 * cart-item and checkout resources accept for it.
 *
 * The configurator key is the one thing a shop decides: it has to name a configurator the shop wires
 * an availability strategy for, so it is set on the suite rather than here:
 * ```yaml
 * - \SprykerTest\Glue\ProductConfigurationsRestApi\Helper\ProductConfigurationDataHelper:
 *       configuratorKey: 'DATE_TIME_CONFIGURATOR'
 * ```
 *
 * A configurable item is only accepted *with* its SKU once the configuration is published.
 * {@see \Spryker\Glue\ProductConfigurationsRestApi\Processor\Validator\CartItemProductConfigurationRestRequestValidator}
 * runs on the request event ahead of API Platform validation and answers 422 unless the configurator
 * key it reads out of storage for that SKU matches the submitted one - which is why the validation
 * suites omit the SKU instead of publishing anything.
 */
class ProductConfigurationDataHelper extends Module
{
    use LocatorHelperTrait;
    use StoreDataHelperTrait;

    /**
     * What the published product configuration carries as its default, and what the cart change
     * expander therefore fills in for an item that arrives without a configuration of its own.
     */
    protected const string CONFIGURATION_DEFAULT = '{"time_of_day":"1"}';

    protected const string DISPLAY_DATA_DEFAULT = '{"Preferred time of the day":"Morning"}';

    protected const int CONFIGURATION_AVAILABLE_QUANTITY = 100;

    /**
     * Deliberately apart from the product's own price, so a calculation that fell back to the
     * product price is visible.
     */
    protected const int CONFIGURATION_NET_AMOUNT = 1234;

    protected const int CONFIGURATION_GROSS_AMOUNT = 1500;

    protected const string CURRENCY_NAME = 'Euro';

    protected const string CURRENCY_SYMBOL = '€';

    /**
     * @uses \Spryker\Shared\PriceProduct\PriceProductConfig::PRICE_TYPE_DEFAULT
     */
    protected const string PRICE_TYPE_NAME = 'DEFAULT';

    protected const string CONFIG_KEY_CONFIGURATOR_KEY = 'configuratorKey';

    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        self::CONFIG_KEY_CONFIGURATOR_KEY,
    ];

    /**
     * Publishes a configuration for an already-sellable product, which is what makes the resource
     * accept an item of it carrying a `productConfigurationInstance`.
     */
    public function publishProductConfigurationFor(ProductConcreteTransfer $productConcreteTransfer): void
    {
        $productConfigurationTransfer = $this->getProductConfigurationHelper()->haveProductConfigurationTransferPersisted([
            ProductConfigurationTransfer::FK_PRODUCT => $productConcreteTransfer->getIdProductConcreteOrFail(),
            ProductConfigurationTransfer::CONFIGURATOR_KEY => $this->configuratorKey(),
            ProductConfigurationTransfer::DEFAULT_CONFIGURATION => static::CONFIGURATION_DEFAULT,
            ProductConfigurationTransfer::DEFAULT_DISPLAY_DATA => static::DISPLAY_DATA_DEFAULT,
        ]);

        $this->publishProductConfiguration($productConfigurationTransfer->getIdProductConfigurationOrFail());
    }

    /**
     * The key the request validator matches the submitted instance against, and what a placed order
     * carries on its configured item.
     */
    public function configuratorKey(): string
    {
        return (string)$this->config[static::CONFIG_KEY_CONFIGURATOR_KEY];
    }

    /**
     * What an item of a configurable product costs: the price comes off the configuration rather
     * than off the product.
     */
    public function configuredUnitPrice(): int
    {
        return static::CONFIGURATION_GROSS_AMOUNT;
    }

    /**
     * A complete instance carrying the configuration the caller wants round-tripped.
     *
     * @return array<string, mixed>
     */
    public function buildProductConfigurationInstanceAttributes(string $configuration, string $displayData): array
    {
        return $this->buildInstanceAttributes($configuration, $displayData, true);
    }

    /**
     * The same instance marked incomplete, which is what stops the checkout accepting the cart.
     *
     * @return array<string, mixed>
     */
    public function buildIncompleteProductConfigurationInstanceAttributes(string $configuration, string $displayData): array
    {
        return $this->buildInstanceAttributes($configuration, $displayData, false);
    }

    /**
     * @param array<string, mixed> $itemResource
     */
    public function readConfiguration(array $itemResource): string
    {
        $productConfigurationInstance = $this->readProductConfigurationInstance($itemResource);

        return (string)$productConfigurationInstance[RestCartItemProductConfigurationInstanceAttributesTransfer::CONFIGURATION];
    }

    /**
     * @param array<string, mixed> $itemResource
     *
     * @return array<string, mixed>
     */
    public function readProductConfigurationInstance(array $itemResource): array
    {
        return $itemResource['attributes'][RestCartItemsAttributesTransfer::PRODUCT_CONFIGURATION_INSTANCE];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildInstanceAttributes(string $configuration, string $displayData, bool $isComplete): array
    {
        return [
            RestCartItemProductConfigurationInstanceAttributesTransfer::DISPLAY_DATA => $displayData,
            RestCartItemProductConfigurationInstanceAttributesTransfer::CONFIGURATION => $configuration,
            RestCartItemProductConfigurationInstanceAttributesTransfer::CONFIGURATOR_KEY => $this->configuratorKey(),
            RestCartItemProductConfigurationInstanceAttributesTransfer::IS_COMPLETE => $isComplete,
            RestCartItemProductConfigurationInstanceAttributesTransfer::AVAILABLE_QUANTITY => static::CONFIGURATION_AVAILABLE_QUANTITY,
            RestCartItemProductConfigurationInstanceAttributesTransfer::PRICES => [
                [
                    RestProductConfigurationPriceAttributesTransfer::PRICE_TYPE_NAME => static::PRICE_TYPE_NAME,
                    RestProductConfigurationPriceAttributesTransfer::NET_AMOUNT => static::CONFIGURATION_NET_AMOUNT,
                    RestProductConfigurationPriceAttributesTransfer::GROSS_AMOUNT => static::CONFIGURATION_GROSS_AMOUNT,
                    RestProductConfigurationPriceAttributesTransfer::CURRENCY => [
                        RestCurrencyTransfer::CODE => $this->getStoreDataHelper()->getCurrentCurrencyCode(),
                        RestCurrencyTransfer::NAME => static::CURRENCY_NAME,
                        RestCurrencyTransfer::SYMBOL => static::CURRENCY_SYMBOL,
                    ],
                ],
            ],
        ];
    }

    /**
     * Runs the real publisher. A suite that reads storage in memory rather than out of the database
     * also gets the written row mirrored into it, which is what its validator then reads.
     */
    protected function publishProductConfiguration(int $idProductConfiguration): void
    {
        $this->getLocator()->productConfigurationStorage()->facade()
            ->writeCollectionByProductConfigurationEvents([
                (new EventEntityTransfer())->setId($idProductConfiguration),
            ]);

        $productConfigurationStorageEntity = SpyProductConfigurationStorageQuery::create()
            ->filterByFkProductConfiguration($idProductConfiguration)
            ->findOne();

        Assert::assertNotNull($productConfigurationStorageEntity, 'The product configuration was not published.');

        if (!$this->hasModule('\\' . StorageHelper::class)) {
            return;
        }

        $this->getStorageHelper()->mockStorageData(
            $productConfigurationStorageEntity->getKey(),
            (string)json_encode($productConfigurationStorageEntity->getData()),
        );
    }

    protected function getStorageHelper(): StorageHelper
    {
        /** @var \SprykerTest\Client\Storage\Helper\StorageHelper $storageHelper */
        $storageHelper = $this->getModule('\\' . StorageHelper::class);

        return $storageHelper;
    }

    protected function getProductConfigurationHelper(): ProductConfigurationHelper
    {
        /** @var \SprykerTest\Shared\ProductConfiguration\Helper\ProductConfigurationHelper $productConfigurationHelper */
        $productConfigurationHelper = $this->getModule('\\' . ProductConfigurationHelper::class);

        return $productConfigurationHelper;
    }
}
