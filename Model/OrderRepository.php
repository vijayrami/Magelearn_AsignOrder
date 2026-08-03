<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magelearn\AsignOrder\Api\OrderRepositoryInterface;

use function __;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Reassign order to a customer by updating customer_id
     *
     * Uses direct SQL to avoid triggering unnecessary observers/plugins
     * while still respecting transaction scope
     */
    public function reassignOrderToCustomer(
        OrderInterface $order,
        CustomerInterface $customer
    ): void {
        try {
            $order->setCustomerId((int) $customer->getId());
            $order->setCustomerIsGuest(false);
            $order->setCustomerGroupId((int) $customer->getGroupId());
            $order->setCustomerFirstname((string) $customer->getFirstname());
            $order->setCustomerLastname((string) $customer->getLastname());
            $order->setCustomerEmail((string) $customer->getEmail());
        } catch (\Exception $e) {
            throw new LocalizedException(
                __(
                    'Failed to reassign order %1: %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                )
            );
        }
    }
}
