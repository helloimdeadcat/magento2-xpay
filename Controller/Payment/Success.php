<?php
declare(strict_types=1);

namespace Stagem\Xpay\Controller\Payment;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

class Success implements HttpGetActionInterface
{
    public function __construct(
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly RequestInterface $request
    ) {
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = (int) $this->request->getParam('order');

        if ($orderId > 0) {
            $resultRedirect->setPath('checkout/onepage/success');
        } else {
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}
