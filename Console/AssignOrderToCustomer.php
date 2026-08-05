<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Console;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface as SalesOrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magelearn\AsignOrder\Api\OrderRepositoryInterface;

use function __;
use function sprintf;

class AssignOrderToCustomer extends Command
{
    private const ARGUMENT_ORDER_INCREMENT_ID = 'order_increment_id';
    private const ARGUMENT_CUSTOMER_ID = 'customer_id';

    public function __construct(
        private readonly State $appState,
        private readonly Share $shareConfig,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('magelearn:order:customer:reassign')
            ->setDescription('Reassign an order from one customer account to another')
            ->addArgument(
                self::ARGUMENT_ORDER_INCREMENT_ID,
                InputArgument::REQUIRED,
                'Magento order increment ID'
            )
            ->addArgument(
                self::ARGUMENT_CUSTOMER_ID,
                InputArgument::REQUIRED,
                'Magento customer ID'
            );

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);

            $orderIncrementId = (string) $input->getArgument(
                self::ARGUMENT_ORDER_INCREMENT_ID
            );

            $customerId = (int) $input->getArgument(
                self::ARGUMENT_CUSTOMER_ID
            );

            $order = $this->orderRepository->getByIncrementId($orderIncrementId);

            $customer = $this->customerRepository->getById(
                $customerId
            );

            $orderWebsiteId = (int) $order->getStore()->getWebsiteId();
            $customerWebsiteId = (int) $customer->getWebsiteId();

            if (!$this->shareConfig->isGlobalScope() && $orderWebsiteId !== $customerWebsiteId) {
                throw new LocalizedException(
                    __('Customer belongs to a different website.')
                );
            }

            if ((int) $order->getCustomerId() === (int) $customer->getId()) {
                throw new LocalizedException(
                    __('Order is already assigned to this customer.')
                );
            }

            $oldCustomerId = (int) $order->getCustomerId();
            $oldCustomerEmail = (string) $order->getCustomerEmail();

            $this->orderRepository->reassignOrderToCustomer(
                $order,
                $customer
            );

            $order->addCommentToStatusHistory(
                __(
                    'Order reassigned via CLI. Previous Customer ID: %1 (%2). New Customer ID: %3 (%4).',
                    $oldCustomerId,
                    $oldCustomerEmail,
                    $customer->getId(),
                    $customer->getEmail()
                )
            );

            $this->salesOrderRepository->save($order);

            $this->logger->info(
                'Order reassigned via CLI',
                [
                    'order_id' => $order->getId(),
                    'order_increment_id' => $order->getIncrementId(),
                    'old_customer_id' => $oldCustomerId,
                    'new_customer_id' => $customer->getId(),
                    'new_customer_email' => $customer->getEmail(),
                ]
            );

            $output->writeln(
                sprintf(
                    '<info>Order #%s successfully reassigned to customer #%d (%s).</info>',
                    $order->getIncrementId(),
                    (int) $customer->getId(),
                    $customer->getEmail()
                )
            );

            return Cli::RETURN_SUCCESS;
        } catch (LocalizedException $exception) {
            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $exception->getMessage()
                )
            );

            return Cli::RETURN_FAILURE;
        } catch (\Exception $exception) {
            $this->logger->error(
                'Order reassignment CLI failed',
                [
                    'order_increment_id' => $orderIncrementId ?? null,
                    'customer_id' => $customerId ?? null,
                    'exception' => $exception->getMessage(),
                ]
            );

            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $exception->getMessage()
                )
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
