<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Assign extends Template
{
    private const ADMIN_URL_POPUP = 'assigncustomer/customer/assign';
    private const ADMIN_URL_GRID = 'assigncustomer/customer/grid';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    public function getAdminPostUrl(): string
    {
        return $this->getUrl(self::ADMIN_URL_POPUP);
    }

    public function getOrderId(): int
    {
        return (int) $this->getRequest()->getParam('order_id');
    }

    public function getOrder(): OrderInterface
    {
        $orderId = $this->getOrderId();
        if (!$orderId) {
            throw new NoSuchEntityException(
                __('Order not found.')
            );
        }

        return $this->orderRepository->get($orderId);
    }

    public function isGuestOrder(): bool
    {
        return (bool) $this->getOrder()->getCustomerIsGuest();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl(self::ADMIN_URL_GRID);
    }
}
