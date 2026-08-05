<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magelearn\AsignOrder\Api\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly CollectionFactory $orderCollectionFactory
    ) {
    }

    /**
     * Updates the order with the specified customer's information.
     *
     * The caller is responsible for persisting the order.
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

    /**
     * Get order by increment ID
     *
     * @throws LocalizedException
     */
    public function getByIncrementId(
        string $incrementId
    ): OrderInterface {
        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter('increment_id', $incrementId)
            ->getFirstItem();

        if (!$order->getId()) {
            throw new LocalizedException(
                __('Order "%1" does not exist.', $incrementId)
            );
        }

        return $order;
    }
}
