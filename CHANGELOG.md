# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-07-11

### Changed

- Renamed Composer package from `stagem/module-xpay` to `helloimdeadcat/magento2-xpay`
- Removed pinned `version` from `composer.json` (versions are managed via Git tags)
- Added Packagist metadata: keywords, homepage, and support links
- Updated README with Composer badges and installation instructions

## [1.0.0] - 2026-07-11

### Added

- XPAY payment method for Magento 2 checkout
- Redirect to XPAY payment widget after order placement
- REST callback endpoint (`GET /rest/V1/xpay/process-pay`) with RSA signature validation
- Automatic invoice creation and invoice email on successful payment
- Admin configuration: API URL, public key, partner ID, customer identification, return URL
- Dedicated logger (`var/log/xpay.log`)
- Composer package metadata and installation documentation

### Changed

- Refactored checkout redirect flow (`setUrl` for external XPAY widget)
- Improved `ConfigProvider` registration for checkout
- Hardened callback validation and error handling
- Payment amount calculation uses rounded minor units (cents)

[1.0.1]: https://github.com/helloimdeadcat/magento2-xpay/releases/tag/v1.0.1
[1.0.0]: https://github.com/helloimdeadcat/magento2-xpay/releases/tag/v1.0.0
