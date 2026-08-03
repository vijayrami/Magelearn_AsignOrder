<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Grid extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Customer::manage';

    public function __construct(
        Action\Context $context,
        private readonly LayoutFactory $layoutFactory,
        private readonly RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Raw
    {
        $layout = $this->layoutFactory->create();
        $grid = $layout
            ->createBlock(
                \Magelearn\AsignOrder\Block\Adminhtml\Customer\Grid::class
            )
            ->toHtml();

        return $this->resultRawFactory
            ->create()
            ->setContents($grid);
    }
}
