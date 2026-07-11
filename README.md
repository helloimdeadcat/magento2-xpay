# Stagem XPAY for Magento 2

[![Packagist Version](https://img.shields.io/packagist/v/helloimdeadcat/magento2-xpay)](https://packagist.org/packages/helloimdeadcat/magento2-xpay)
[![Packagist Downloads](https://img.shields.io/packagist/dt/helloimdeadcat/magento2-xpay)](https://packagist.org/packages/helloimdeadcat/magento2-xpay)
[![License: MIT](https://img.shields.io/github/license/helloimdeadcat/magento2-xpay)](LICENSE.txt)

Magento 2 payment module for [XPAY](https://xpay.com.ua). After placing an order, the customer is redirected to the XPAY payment widget. Payment confirmation is handled via a REST callback.

## Requirements

- PHP 8.1+
- Magento 2.4.x
- OpenSSL

## Installation

### Composer (recommended)

From your Magento 2 project root:

```bash
composer require helloimdeadcat/magento2-xpay:^1.0
bin/magento module:enable Stagem_Xpay
bin/magento setup:upgrade
bin/magento cache:flush
```

> **Note:** The Magento module code namespace is `Stagem\Xpay` (`Stagem_Xpay`). The Composer package name is `helloimdeadcat/magento2-xpay`.

### Composer (before Packagist registration)

If the package is not yet on [Packagist](https://packagist.org), add the GitHub repository to your Magento root `composer.json`:

```json
"repositories": {
    "magento2-xpay": {
        "type": "vcs",
        "url": "https://github.com/helloimdeadcat/magento2-xpay"
    }
}
```

Then run `composer require helloimdeadcat/magento2-xpay:^1.0` as above.

### Manual

1. Copy the module to `app/code/Stagem/Xpay`
2. Run:

```bash
bin/magento module:enable Stagem_Xpay
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

Go to **Stores → Configuration → Sales → Payment Methods → Xpay** and configure:

| Field | Description |
|-------|-------------|
| XPAY API URL | API base URL (default: `https://mapi.xpay.com.ua`) |
| Xpay public key | Partner public key provided by XPAY |
| Partner ID | Partner ID provided by XPAY |
| User identified by | Identify the customer by email or phone number |
| Return URL | Success page path (default: `xpay/payment/success`) |

## How it works

1. The customer selects XPAY at checkout and places the order.
2. Magento redirects to `xpay/payment/checkout?order={id}`.
3. The module builds the XPAY widget URL and redirects the customer.
4. XPAY sends a callback to `GET /rest/V1/xpay/process-pay`.
5. The module validates the signature, creates an invoice, and sends the invoice email.
6. The customer is redirected back to the Magento success page.

## Logging

Module events are written to `var/log/xpay.log`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE.txt](LICENSE.txt).
