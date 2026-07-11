<?php
declare(strict_types=1);

namespace Stagem\Xpay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'xpay';

    public function __construct(
        private readonly PaymentHelper $paymentHelper,
        private readonly Escaper $escaper
    ) {
    }

    public function getConfig(): array
    {
        $method = $this->paymentHelper->getMethodInstance(self::CODE);

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $method->isAvailable(),
                    'title' => $this->escaper->escapeHtml($method->getTitle()),
                    'description' => $this->escaper->escapeHtml((string) $method->getConfigData('description')),
                ],
            ],
        ];
    }
}
