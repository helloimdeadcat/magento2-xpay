<?php
declare(strict_types=1);

namespace Stagem\Xpay\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Stagem\Xpay\Api\ProcessPayInterface;
use Stagem\Xpay\Helper\PaymentHelper;

class ProcessPay implements ProcessPayInterface
{
    private const OPERATION_STATUS_SUCCESS = '10';
    private const OPERATION_STATUS_FAILED = '21';
    private const COMMAND_PAY = 'pay';

    public function __construct(
        private readonly PaymentHelper $paymentHelper,
        private readonly Request $request,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processPayGet(): array
    {
        $response = $this->buildResponse(self::OPERATION_STATUS_FAILED, 'Request parameters are empty');
        $data = $this->request->getParams();

        if (empty($data)) {
            return $response;
        }

        $response['txn_id'] = $data['txn_id'] ?? '';
        $response['message'] = 'Something went wrong';

        $requiredFields = ['txn_id', 'uuid', 'txn_date', 'sum', 'sign', 'command'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $response['message'] = sprintf('%s is required', $field);
                $this->logger->error('XPAY callback: missing field ' . $field);
                return $response;
            }
        }

        $signaturePayload = $data['txn_id'] . $data['uuid'] . $data['txn_date'] . $data['sum'];
        if (!$this->paymentHelper->validateResponse($signaturePayload, $data['sign'])) {
            $response['message'] = 'Signature not valid';
            $this->logger->error('XPAY callback: invalid signature for order ' . $data['txn_id']);
            return $response;
        }

        if ($data['command'] !== self::COMMAND_PAY) {
            $response['message'] = 'Only pay command supported';
            $this->logger->error('XPAY callback: unsupported command ' . $data['command']);
            return $response;
        }

        try {
            $order = $this->orderRepository->get((int) $data['txn_id']);
        } catch (\Exception $exception) {
            $response['message'] = 'Error while getting order by txn_id';
            $this->logger->error('XPAY callback: order not found', ['txn_id' => $data['txn_id']]);
            return $response;
        }

        if (!$order->canInvoice()) {
            $response['message'] = 'Order cannot be invoiced';
            $this->logger->error('XPAY callback: order cannot be invoiced', ['order_id' => $order->getId()]);
            return $response;
        }

        if ($this->processOrder($order)) {
            $response = $this->buildResponse(self::OPERATION_STATUS_SUCCESS, 'Ok', (string) $order->getId());
        }

        return $response;
    }

    private function buildResponse(string $result, string $message, string $txnId = ''): array
    {
        return [
            'result' => $result,
            'message' => $message,
            'txn_id' => $txnId,
            'date_time' => date('YmdHis'),
        ];
    }

    private function processOrder(OrderInterface $order): bool
    {
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice || !$invoice->getTotalQty()) {
                throw new LocalizedException(__('Unable to create invoice for this order.'));
            }

            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);

            $transaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transaction->save();

            $order->addStatusHistoryComment((string) __('Automatically invoiced by XPAY'), false);

            try {
                $this->invoiceSender->send($invoice);
                $order->addCommentToStatusHistory((string) __('Notified customer about invoice creation.'))
                    ->setIsCustomerNotified(true)
                    ->save();
            } catch (\Exception $exception) {
                $this->logger->error('XPAY callback: unable to send invoice email', ['order_id' => $order->getId()]);
            }

            $this->logger->info('XPAY callback: invoice created', ['order_id' => $order->getId()]);
            return true;
        } catch (\Exception $exception) {
            $this->logger->error(
                'XPAY callback: invoice creation failed',
                ['order_id' => $order->getId(), 'error' => $exception->getMessage()]
            );
            return false;
        }
    }
}
