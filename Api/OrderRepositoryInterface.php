<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

interface OrderRepositoryInterface
{
    /**
     * Reassign order to a customer
     *
     * @param OrderInterface $order
     * @param CustomerInterface $customer
     * @return void
     * @throws LocalizedException
     */
    public function reassignOrderToCustomer(OrderInterface $order, CustomerInterface $customer): void;
}
