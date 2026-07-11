<?php
declare(strict_types=1);

namespace Stagem\Xpay\Block;

use Magento\Framework\View\Element\Template;

class Xpay extends Template
{
    public function getXpayConfig(): array
    {
        return [
            'xpayImageUrl' => $this->getViewFileUrl('Stagem_Xpay::images/xpay_logo.png'),
        ];
    }
}
