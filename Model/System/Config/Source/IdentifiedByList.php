<?php
declare(strict_types=1);

namespace Stagem\Xpay\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Stagem\Xpay\Helper\PaymentHelper;

class IdentifiedByList implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => PaymentHelper::IDENTIFIED_BY_EMAIL, 'label' => __('Customer Email')],
            ['value' => PaymentHelper::IDENTIFIED_BY_PHONE, 'label' => __('Customer Phone')],
        ];
    }
}
