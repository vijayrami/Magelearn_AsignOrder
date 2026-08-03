<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

interface OrderMigrationServiceInterface
{
    /**
     * Migrate a guest order to a customer account
     *
     * @param OrderInterface $order Guest order to migrate
     * @param CustomerInterface $customer Target customer account
     * @return void
     * @throws LocalizedException
     */
    public function migrateOrderToCustomer(
        OrderInterface $order,
        CustomerInterface $customer
    ): void;
}
