<?php
declare(strict_types=1);

namespace Stagem\Xpay\Controller\Payment;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Stagem\Xpay\Helper\PaymentHelper;

class Checkout implements HttpGetActionInterface
{
    public function __construct(
        private readonly PaymentHelper $paymentHelper,
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = (int) $this->request->getParam('order');

        if ($orderId <= 0) {
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        try {
            $paymentUrl = $this->paymentHelper->getPaymentLink($orderId);
            $resultRedirect->setUrl($paymentUrl);
        } catch (LocalizedException $exception) {
            $this->logger->error('XPAY checkout redirect failed: ' . $exception->getMessage());
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}
