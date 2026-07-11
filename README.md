# Stagem XPAY for Magento 2

Модуль оплаты [XPAY](https://xpay.com.ua) для Magento 2. После оформления заказа покупатель перенаправляется на платёжный виджет XPAY, а подтверждение оплаты обрабатывается через REST callback.

## Требования

- PHP 8.1+
- Magento 2.4.x
- OpenSSL

## Установка

### Composer

```bash
composer require stagem/module-xpay
bin/magento module:enable Stagem_Xpay
bin/magento setup:upgrade
bin/magento cache:flush
```

### Вручную

1. Скопируйте модуль в `app/code/Stagem/Xpay`
2. Выполните:

```bash
bin/magento module:enable Stagem_Xpay
bin/magento setup:upgrade
bin/magento cache:flush
```

## Настройка

Перейдите в **Stores → Configuration → Sales → Payment Methods → Xpay** и укажите:

| Поле | Описание |
|------|----------|
| XPAY API URL | Базовый URL API (по умолчанию `https://mapi.xpay.com.ua`) |
| Xpay public key | Публичный ключ партнёра |
| Partner ID | ID партнёра |
| User identified by | Идентификация покупателя по email или телефону |
| Return URL | Путь success-страницы (по умолчанию `xpay/payment/success`) |

## Как это работает

1. Покупатель выбирает XPAY на checkout и оформляет заказ.
2. Magento перенаправляет на `xpay/payment/checkout?order={id}`.
3. Модуль формирует ссылку на виджет XPAY и перенаправляет покупателя.
4. XPAY отправляет callback на `GET /rest/V1/xpay/process-pay`.
5. Модуль проверяет подпись, создаёт invoice и отправляет письмо.
6. Покупатель возвращается на success-страницу Magento.

## Логи

События модуля пишутся в `var/log/xpay.log`.

## Лицензия

MIT — см. [LICENSE.txt](LICENSE.txt).
