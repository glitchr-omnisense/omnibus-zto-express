# omnibus/zto-express

ZTO Express (中通快递) for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus): orders
with electronic waybills, tracking and cancellation - the open platform's signed JSON APIs.
Prices come from configuration (`rates`): ZTO quotes by contract.

## Install and a first call

```sh
composer require omnibus/zto-express
```

No account for this first call: ZTO Express's answer is recorded
(`Omnibus\Testing\RecordedHttpClient` answers in place of the API), so nothing leaves the process.
With your own credentials, give the factory your HTTP client (or none: it makes its own) and drop
the recorded one.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnibus\Testing\RecordedHttpClient;
use Omnibus\ZtoExpress\ZtoExpressGatewayFactory;

// ZTO Express's answer, recorded: no account, and nothing leaves the process.
$http = new RecordedHttpClient([
    '/zto.open.getTraceInfo' => ['status' => true, 'result' => [[
        ['billCode' => '75123456789012', 'scanType' => '签收', 'scanDate' => '2026-10-02 11:30:00', 'scanSite' => '北京朝阳', 'desc' => '快件已签收'],
    ]]],
]);
$gateway = (new ZtoExpressGatewayFactory($http))->create(['company_id' => 'demo', 'key' => 'k', 'partner_code' => 'P001', 'sandbox' => true]);

$tracking = $gateway->track('75123456789012');
echo $tracking->status->value, ' - ', $tracking->latest()?->description, "\n";   // delivered - 快件已签收
```

A real call needs a ZTO Express account (the credentials below). **Unverified**: the request and
answer shapes come from ZTO Express's published documentation, and the package has not been run
against its service.

## Configuration

```php
$gateway = (new ZtoExpressGatewayFactory($http))->create($options);   // $http: the application's HTTP client - none given, the factory makes its own; the options below
```

No framework needed: the package requires `omnisense/omnibus` and `symfony/http-client`. In a
Symfony application, the same through the bundle's configuration:

```yaml
omnibus:
    gateways:
        zto_express:
            factory: zto_express
            options:
                company_id: '%env(ZTO_COMPANY_ID)%'
                key: '%env(ZTO_KEY)%'
                partner_code: '%env(ZTO_PARTNER)%'   # 合作商编码, for electronic waybills
                sandbox: true
                rates: [...]
```

Addresses: the first street line is the street, the second the district (区), the third the
province (省). The label is ZTO's print data (JSON: the sorting marks), rendered by your own
template. Shipment options: `type` (1 by default), `description`, `instructions`. No pickup
points.

Credentials: a company on [ZTO's open platform](https://open.zto.com) gives the company id and
key (the test environment first) and the partner code for electronic waybills.

Built from ZTO's published open platform documentation and tested on recorded answers;
**unverified** against the test environment until an account's keys are at hand.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by ZTO Express (Cayman) Inc. It reaches their service on behalf of whoever runs it, and
it does not redistribute their data beyond the short excerpts recorded as test fixtures. Its MIT
licence covers this package's code only and grants no right to the service or to its data.

Whoever installs and runs this package is responsible for complying with ZTO Express's terms (those
of its open platform, accepted on registration: [open.zto.com](https://open.zto.com/)), for
obtaining whatever they require (an account, a key, a licence or subscription, an attribution or a
citation, a rate limit to respect), and for any use made of what it returns. The authors publish it
free of charge, make no commercial use of it, and accept no liability for how it is used (see
[LICENSE](LICENSE)).

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
