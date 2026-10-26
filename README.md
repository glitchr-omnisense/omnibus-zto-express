# omnibus/zto-express

ZTO Express (中通快递) for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus): orders
with electronic waybills, tracking and cancellation - the open platform's signed JSON APIs.
Prices come from configuration (`rates`): ZTO quotes by contract.

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
