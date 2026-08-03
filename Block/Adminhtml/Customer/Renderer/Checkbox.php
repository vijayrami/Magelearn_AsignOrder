<?php

declare(strict_types=1);

namespace Magelearn\AsignOrder\Block\Adminhtml\Customer\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Checkbox extends AbstractRenderer
{
    public function render(DataObject $row): string
    {
        return sprintf(
            '<input type="radio"
                name="selected_customer"
                value="%d" />',
            (int) $row->getId()
        );
    }
}
