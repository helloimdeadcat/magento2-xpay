<?php
declare(strict_types=1);

namespace Stagem\Xpay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PaymentHelper extends AbstractHelper
{
    public const XML_PATH_SHOW_PAYMENT_INFO = 'payment/xpay/show_payment_info';
    public const XML_PATH_RETURN_URL = 'payment/xpay/return_url';
    public const XML_PATH_XPAY_URL = 'payment/xpay/xpay_url';
    public const XML_PATH_PARTNER_ID = 'payment/xpay/pid';
    public const XML_PATH_XPAY_PUBLIC_KEY = 'payment/xpay/xpay_public_key';
    public const XML_PATH_IDENTIFIED_BY = 'payment/xpay/identified_by';
    public const IDENTIFIED_BY_PHONE = 'phone';
    public const IDENTIFIED_BY_EMAIL = 'email';
    public const REST_API_URI = 'rest/V1/xpay/process-pay';

    public function __construct(
        Context $context,
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RemoteAddress $remoteAddress,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPaymentLink(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $address = $this->getOrderAddress($order);

        $email = (string) ($address?->getEmail() ?: $order->getCustomerEmail());
        $telephone = $this->normalizePhone((string) ($address?->getTelephone() ?? ''));

        $identifiedBy = (string) $this->scopeConfig->getValue(self::XML_PATH_IDENTIFIED_BY, 'store');
        $account = $identifiedBy === self::IDENTIFIED_BY_PHONE ? $telephone : $email;

        if ($account === '') {
            throw new LocalizedException(__('Customer identification data is missing for XPAY payment.'));
        }

        $baseUrl = rtrim((string) $this->scopeConfig->getValue(self::XML_PATH_XPAY_URL, 'store'), '/');
        $sum = (int) round((float) $order->getGrandTotal() * 100);
        $data = $this->buildPaymentData($order, $address);

        return $baseUrl . '/uk/frame/widget/banner-payment'
            . '?pid=' . rawurlencode((string) $this->scopeConfig->getValue(self::XML_PATH_PARTNER_ID, 'store'))
            . '&acc=' . rawurlencode($account)
            . '&sum=' . $sum
            . '&data=' . $data;
    }

    public function validateResponse(string $stringData, string $signature): bool
    {
        $publicKey = (string) $this->scopeConfig->getValue(self::XML_PATH_XPAY_PUBLIC_KEY, 'store');
        if ($publicKey === '') {
            $this->logger->error('XPAY public key is not configured');
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        return (bool) openssl_verify($stringData, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
    }

    public function getOrder(int $orderId): OrderInterface
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * @throws NoSuchEntityException
     */
    private function buildPaymentData(OrderInterface $order, ?OrderAddressInterface $address): string
    {
        $paymentInfo = [];

        if ($this->scopeConfig->isSetFlag(self::XML_PATH_SHOW_PAYMENT_INFO, 'store')) {
            foreach ($order->getAllVisibleItems() as $product) {
                $paymentInfo[] = [
                    'Caption' => $product->getName(),
                    'Value' => $product->getRowTotal(),
                ];
            }
        }

        $paymentData = [
            'Email' => (string) $order->getCustomerEmail(),
            'Phone' => $this->normalizePhone((string) ($address?->getTelephone() ?? '')),
            'FirstName' => (string) $order->getCustomerFirstname(),
            'LastName' => (string) $order->getCustomerLastname(),
            'ClientIP' => (string) $this->remoteAddress->getRemoteAddress(),
            'txn_id' => (string) $order->getId(),
            'Currency' => (string) $order->getOrderCurrencyCode(),
            'PaymentInfo' => $paymentInfo,
            'CallBackURL' => $this->getCallbackUrl(),
            'Callback' => [
                'PaySuccess' => [
                    'URL' => $this->getCallbackSuccessUrl((int) $order->getId()),
                ],
            ],
        ];

        $jsonData = json_encode($paymentData, JSON_THROW_ON_ERROR);
        $gzData = gzencode($jsonData);

        return rawurlencode(base64_encode($gzData !== false ? $gzData : $jsonData));
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getCallbackSuccessUrl(int $orderId): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $returnUrl = trim((string) $this->scopeConfig->getValue(self::XML_PATH_RETURN_URL, 'store'));

        return $baseUrl . $returnUrl . '?order=' . $orderId;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . self::REST_API_URI;
    }

    private function getOrderAddress(OrderInterface $order): ?OrderAddressInterface
    {
        return $order->getShippingAddress() ?: $order->getBillingAddress();
    }

    private function normalizePhone(string $phone): string
    {
        return str_replace(['+', '-', '(', ')', ' '], '', $phone);
    }
}
