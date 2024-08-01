# lib-pg-duitku

## Instalasi

Jalankan perintah di bawah di folder aplikasi:

```
mim app install lib-pg-duitku
```

## Penggunaan

### LibPgDuitku\Library\Transfer

**lastError()**

Mengembalikan error yang terjadi terakhir kali.

**check(array $data)**

Cek status transfer berdasarkan disburseId.

```php
$data = [
    'disburseId' => ::String
];
```

```
stdClass Object
(
    [bankCode] => 014
    [bankAccount] => 8760673566
    [amountTransfer] => 10000
    [accountName] => Test Account
    [custRefNumber] => 000000541835
    [responseCode] => 00
    [responseDesc] => SUCCESS
)
```

**inquiry(array $data, string $type)**

Inquiry bank account.

```php
$data = [
    'resume' => [
        'amount' => 10000
    ],
    'bank' => [
        'code' => '014',
        'account' => [
            // 'number' => '8760673566' // ONLINE
            'number' => '8760673466' // BIFAST
        ]
    ],
    'info' => 'Test Online',
    'user' => [
        'id' => 1,
        'name' => 'MIM Engine'
    ]
];
$type = ::String // 'LLG', 'RTGS', 'H2H', 'BIFAST', 'ONLINE'
```

```
stdClass Object
(
    [email] => test@chakratechnology.com
    [bankCode] => 014
    [bankAccount] => 8760673466
    [amountTransfer] => 10000
    [accountName] => Test Account
    [custRefNumber] => 000000541831
    [disburseId] => 592273
    [type] => BIFAST
    [responseCode] => 00
    [responseDesc] => Approved or completed successfully
)
```

**bifast(array $data)**

Shortcut untuk `send($data, 'BIFAST')`.

**online(array $data)**

Shortcut untuk `send($data, 'ONLINE')`

**send(array $data, string $type)**

Eksekusi pengiriman dana

```php
$data = [
    'user' => [
        'id' => 1,
        'name' => 'User Name'
    ],
    'resume' => [
        'amount' => (float)10000.0
    ],
    'bank' => [
        'name' => 'BANK CENTRAL ASIA',
        'code' => '014',
        'account' => [
            'name' => 'Test Account',
            // 'number' => '8760673566' // ONLINE,
            'number' => '8760673466' // BIFAST
        ]
    ],
    'info' => 'Penarikan Dana'
];

$type = ::String // 'LLG', 'RTGS', 'H2H', 'BIFAST', 'ONLINE'
```

```
stdClass Object
(
    [email] => test@chakratechnology.com
    [bankCode] => 014
    [bankAccount] => 8760673466
    [amountTransfer] => 10000
    [accountName] => Test Account
    [custRefNumber] => 000000541836
    [type] => BIFAST // Non ONLINE only
    [responseCode] => 00
    [responseDesc] => Approved or completed successfully
    [disburseId] => 592278
)
```

**getBanks()**

Mengambil semua daftar bank

```
stdClass Object
(
    [responseCode] => 00
    [responseDesc] => Success
    [Banks] => Array
        (
            [0] => stdClass Object
                (
                    [bankCode] => 014
                    [bankName] => BANK CENTRAL ASIA
                    [maxAmountTransfer] => 100000000
                )
        )
)
```
