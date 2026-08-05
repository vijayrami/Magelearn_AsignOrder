<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Console;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Handler\State as OrderStateHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\QuestionFactory;

use function __;
use function sprintf;

class OrderCheck extends Command
{
    public const OPTION_INCREMENT_ID = 'increment-id';
    public const OPTION_FROM_DATE = 'from-date';
    public const OPTION_TO_DATE = 'to-date';
    public const OPTION_SAVE = 'save';
    private State $state;

    private OrderRepositoryInterface $orderRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private OrderStateHandler $orderStateHandler;

    private QuestionFactory $questionFactory;

    private bool $break = false;

    public function __construct(
        State $state,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderStateHandler $orderStateHandler,
        QuestionFactory $questionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->state = $state;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderStateHandler = $orderStateHandler;
        $this->questionFactory = $questionFactory;
    }

    protected function configure(): void
    {
        $this->setName('magelearn:order:check')
            ->setDescription('Check and optionally correct order state and status')
            ->addOption(self::OPTION_INCREMENT_ID, null, InputOption::VALUE_OPTIONAL, 'Order Id')
            ->addOption(self::OPTION_FROM_DATE, null, InputOption::VALUE_OPTIONAL, 'Order Created from Date')
            ->addOption(self::OPTION_TO_DATE, null, InputOption::VALUE_OPTIONAL, 'Order Created until Date')
            ->addOption(self::OPTION_SAVE, 's', InputOption::VALUE_NONE, 'Save order with corrected state/status');

        parent::configure();
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        if (
            $input->getOption(self::OPTION_INCREMENT_ID)
            || $input->getOption(self::OPTION_FROM_DATE)
            || $input->getOption(self::OPTION_TO_DATE)
        ) {
            return;
        }

        /** @var Question $question */
        $question = $this->questionFactory->create([
            'question' => '<question>You have specified no filters, which means all orders '
                . 'will be checked and it might take very long time. '
                . 'Are you sure you want to continue? [Y/n]:</question> ',
            'default' => '',
        ]);

        if ($questionHelper->ask($input, $output, $question) === 'Y') {
            return;
        }

        $this->break = true;
    }

    /**
     * @return int|void|null
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->break) {
            return Cli::RETURN_SUCCESS;
        }

        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $incrementId = $input->getOption(self::OPTION_INCREMENT_ID);

        if ($incrementId) {
            $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId);
        }

        $fromDate = $input->getOption(self::OPTION_FROM_DATE);

        if ($fromDate) {
            $this->searchCriteriaBuilder->addFilter('created_at', $fromDate, 'gteq');
        }

        $toDate = $input->getOption(self::OPTION_TO_DATE);

        if ($toDate) {
            $this->searchCriteriaBuilder->addFilter('created_at', $toDate, 'lteq');
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResult->getItems();

        if (empty($orders) && $incrementId) {
            $output->writeln(
                sprintf(
                    '<error>Order "%s" was not found.</error>',
                    $incrementId
                )
            );

            return Cli::RETURN_FAILURE;
        }

        if (empty($orders)) {
            $output->writeln(
                '<comment>No orders matched the specified filters.</comment>'
            );

            return Cli::RETURN_FAILURE;
        }

        /** @var Order $order */
        foreach ($orders as $order) {
            $output->write($order->getIncrementId() . ' ' . $order->getState() . ' / ' . $order->getStatus() . ' ');

            $state = $order->getState();
            $status = $order->getStatus();
            $this->orderStateHandler->check($order);

            if ($order->getState() == $state && $order->getStatus() == $status) {
                $output->writeln('<info>[ OK ]</info>');
                continue;
            }

            $output->write(sprintf('<error>[ %s / %s ]</error> ', $order->getState(), $order->getStatus()));

            if (!$input->getOption(self::OPTION_SAVE)) {
                $output->writeln('');
                continue;
            }

            $comment = __(
                'Order state/status fixed.<br/>State changed from %1 to %2.<br/>Status changed from %3 to %4.',
                $state,
                $order->getState(),
                $status,
                $order->getStatus()
            );

            $order->addCommentToStatusHistory($comment);
            $this->orderRepository->save($order);

            $output->writeln('<info>[ Saved ]</info>');
        }

        return Cli::RETURN_SUCCESS;
    }
}
