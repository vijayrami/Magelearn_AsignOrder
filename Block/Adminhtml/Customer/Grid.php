<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Store\Model\System\Store;
use Magelearn\AsignOrder\Block\Adminhtml\Customer\Renderer\Checkbox;

class Grid extends Extended
{
    private const ADMIN_URL_GRID = 'assigncustomer/customer/grid';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        private readonly CollectionFactory $collectionFactory,
        private readonly Store $systemStore,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    protected function _construct(): void
    {
        parent::_construct();

        $this->setId('customer_assign_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection(): self
    {
        $collection = $this->collectionFactory->create();

        $collection->addAttributeToSelect([
            'firstname',
            'lastname',
            'email',
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): self
    {
        $this->addColumn(
            'select_customer',
            [
                'header' => __('Select'),
                'renderer' =>
                    Checkbox::class,
                'filter' => false,
                'sortable' => false,
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
            ]
        );

        $this->addColumn(
            'firstname',
            [
                'header' => __('First Name'),
                'index' => 'firstname',
            ]
        );

        $this->addColumn(
            'lastname',
            [
                'header' => __('Last Name'),
                'index' => 'lastname',
            ]
        );

        $this->addColumn(
            'email',
            [
                'header' => __('Email'),
                'index' => 'email',
            ]
        );

        $this->addColumn(
            'website_id',
            [
                'header' => __('Website'),
                'index' => 'website_id',
                'type' => 'options',
                'options' => $this->systemStore->getWebsiteOptionHash(),
            ]
        );

        return parent::_prepareColumns();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl(
            self::ADMIN_URL_GRID,
            ['_current' => true]
        );
    }
}
