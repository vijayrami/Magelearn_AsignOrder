<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magelearn\AsignOrder\Api\OrderMigrationServiceInterface;

class AssignGuestOrder extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Customer::manage';

    public function __construct(
        Context $context,
        private readonly Session $authSession,
        private readonly Share $shareConfig,
        private readonly JsonFactory $resultJsonFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
        private readonly OrderMigrationServiceInterface $orderMigrationService
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $customerId = (int) $this->getRequest()->getParam('customer_id');

            if (!$orderId || !$customerId) {
                throw new LocalizedException(
                    __('Missing order or customer information.')
                );
            }

            $order = $this->orderRepository->get($orderId);
            $customer = $this->customerRepository->getById($customerId);

            $orderWebsiteId = (int) $order->getStore()->getWebsiteId();
            $customerWebsiteId = (int) $customer->getWebsiteId();

            if (!$order->getCustomerIsGuest()) {
                throw new LocalizedException(
                    __('This order is already assigned to a customer.')
                );
            }

            if (!$this->shareConfig->isGlobalScope() && $orderWebsiteId !== $customerWebsiteId) {
                throw new LocalizedException(
                    __('Customer belongs to a different website.')
                );
            }

            $adminUser = $this->authSession->getUser();

            $adminUsername = (string) $adminUser?->getUserName();

            $this->orderMigrationService->migrateOrderToCustomer(
                $order,
                $customer
            );

            $comment = __(
                'Order assigned to customer account by admin "%1". Customer ID: %2, Email: %3.',
                $adminUsername,
                $customer->getId(),
                $customer->getEmail(),
            );

            $order->addCommentToStatusHistory($comment);

            $this->orderRepository->save($order);

            $this->logger->info(
                'Guest order migrated',
                [
                    'order_id' => $order->getId(),
                    'order_increment_id' => $order->getIncrementId(),
                    'customer_id' => $customer->getId(),
                    'customer_email' => $customer->getEmail(),
                    'admin_user' => $adminUsername,
                ]
            );

            $resultJson->setData([
                'error' => false,
                'message' => __(
                    'Order #%1 assigned to customer %2.',
                    $order->getIncrementId(),
                    $customer->getEmail()
                ),
                'redirectUrl' => $this->_url->getUrl(
                    'sales/order/view',
                    ['order_id' => $order->getId()]
                ),
            ]);
        } catch (LocalizedException $e) {
            $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to assign customer to guest order',
                [
                    'order_id' => $orderId ?? null,
                    'customer_id' => $customerId ?? null,
                    'exception' => $e->getMessage(),
                ]
            );

            $resultJson->setData([
                'error' => true,
                'message' => __('Something went wrong while assigning the order.'),
            ]);
        }

        return $resultJson;
    }
}
