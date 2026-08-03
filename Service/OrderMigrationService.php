<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Service;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Magelearn\AsignOrder\Api\OrderMigrationServiceInterface;
use Magelearn\AsignOrder\Api\OrderRepositoryInterface;

use function __;

class OrderMigrationService implements OrderMigrationServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Migrate a guest order to a customer account
     *
     * This method:
     * 1. Validates the order is a guest order
     * 2. Updates the order.customer_id in a database transaction
     * 3. Logs the migration for audit purposes
     * 4. Handles failures gracefully with rollback
     *
     * @throws LocalizedException
     */
    public function migrateOrderToCustomer(
        OrderInterface $order,
        CustomerInterface $customer
    ): void {
        // Validate: order must be a guest order
        if (!$order->getCustomerIsGuest()) {
            throw new LocalizedException(
                __('Order #%1 is already assigned to a customer.', $order->getIncrementId())
            );
        }

        try {
            // Update order with new customer_id
            $this->orderRepository->reassignOrderToCustomer(
                $order,
                $customer
            );

            $this->logger->info(
                'Guest order migrated',
                [
                    'order_id' => $order->getId(),
                    'order_increment_id' => $order->getIncrementId(),
                    'customer_id' => $customer->getId(),
                    'customer_email' => $customer->getEmail(),
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to migrate guest order',
                [
                    'order_id' => $order->getId(),
                    'customer_id' => $customer->getId(),
                    'error' => $e->getMessage(),
                ]
            );

            throw new LocalizedException(
                __('Failed to migrate order #%1: %2', $order->getIncrementId(), $e->getMessage()),
                $e
            );
        }
    }
}
