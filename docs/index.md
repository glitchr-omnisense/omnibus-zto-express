# omnibus/zto-express

ZTO Express (中通快递) for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus): orders
with electronic waybills, tracking and cancellation, through ZTO's open platform (signed JSON
APIs). ZTO quotes by contract: prices come from the `rates` option. The gateway posts to one path
per message type, on `https://japi.zto.com` (live) or `https://japi-test.zto.com` (the test
environment, `sandbox: true`):

| Operation | Message type |
|---|---|
| ship | `zto.open.createOrder` |
| track | `zto.open.getTraceInfo` |
| cancel | `zto.open.cancelOrder` |

## Installation

```sh
composer require omnibus/zto-express
```

Requires PHP 8.2, `omnisense/omnibus` (2.x) and `symfony/http-client` (6.4, 7 or 8); given no
HTTP client, the factory makes one with `HttpClient::create()`. In a Symfony application with
`Omnibus\Bridge\Symfony\OmnibusBundle`, the factory is registered as soon as the package is
installed.

## Credentials

- A company on [ZTO's open platform](https://open.zto.com): its **company id** and **key** (the
  test environment first).
- The **partner code** (合作商编码) for electronic waybills.

Every call sends the JSON body with the headers `x-companyId` and `x-dataDigest` =
`base64(md5(body + key))` (`Omnibus\ZtoExpress\Api::digest()`), with a 20-second timeout.

## Options

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `company_id` | string | - | yes | the open platform's company id |
| `key` | string | - | yes | its key, which signs every call |
| `partner_code` | ?string | `null` | no | the partner code sent as `partner` with each order; not sent when unset |
| `sandbox` | bool | `false` | no | `true`: the test environment |
| `rates` | list | - | no | the only way this gateway rates ([the rates option](https://github.com/glitchr-omnisense/omnibus/blob/2.x/docs/configuration.md#the-rates-option)) |

Shipment options read when booking (`Shipment::$options`):

| Option | Default | |
|---|---|---|
| `type` | `1` | ZTO's order type |
| `description` | `商品` | each item's name |
| `instructions` | `''` | the order's remark, 100 characters |

## Capabilities

| Request | Supported |
|---|---|
| `Rating` | only with `rates` (configured prices) |
| `Shipping` | yes |
| `Tracking` | yes |
| `Pickup` | no |
| `GetSlip` | no |
| `Cancel` | yes |
| `Paperless` | no |

## Behaviour

**Answers.** An envelope without a true `status` is an error; its `result` (else `data`) is the
answer.

**Addresses.** ZTO wants Chinese addresses in parts, taken from the street lines: the **first
street line is the street address, the second the district (区), the third the province (省)**;
the city is the address's city. A party also sends its name, company, the phone as mobile and
phone, and its postcode.

**Ship.** `zto.open.createOrder`: the `partner_code`, the order id (the shipment's `reference`,
else `omnibus-` and 12 random hexadecimal characters), the `type`, the reference as trade id, the
order sum (the parcels' `value` added up, in major units), sender and receiver, one item per
parcel (the `description`, quantity 1, the weight in kilograms rounded to two decimals, at least
0.1), the total weight, the `instructions` as remark. Shipment services and dimensions are not
sent. The tracking number is the `billCode` (else `mailNo`). The label is ZTO's print data (the
sorting marks of the electronic waybill), JSON-encoded into `content`, with the format
`application/json`: it is rendered by the shop's own template, not a document to print as is.
`trackingUrl` is `https://www.zto.com/express/expressCheck.html?txtbill=<number>`. A response
without a bill code is a `CarrierException` (`ZTO issued no bill code.`). `pickupPoint` is not
sent.

**Track.** `zto.open.getTraceInfo` for the bill code; the locale is not sent. Each trace with a
`scanDate`: the date read in `Asia/Shanghai`, `desc` (else the scan type) as description,
`scanSite` (else `scanCity`) as location, the scan type as `code`; sorted oldest first. The status
is the latest trace's, read in this order:

| Scan type | Or the description contains | `TrackingStatus` |
|---|---|---|
| `签收` | `已签收`, `delivered` | `DELIVERED` |
| `派件` | `派件`, `out for delivery` | `OUT_FOR_DELIVERY` |
| `退回` | `退回`, `return` | `RETURNED` |
| `问题件` | `问题件`, `异常`, `exception` | `EXCEPTION` |
| `收件`, `发件`, `到件`, `到达`, `发出` | `已收件`, `collected`, or any other text | `IN_TRANSIT` |
| anything else, without a description | | `UNKNOWN` |

No trace is ever `PENDING` or `AVAILABLE_FOR_PICKUP`.

**Cancel.** `zto.open.cancelOrder` by bill code, reason `cancelled by the shipper`: `true` when the
result's value is absent, `true`, `1` or `'true'`.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Omnibus\ZtoExpress\ZtoExpressGatewayFactory;
use Symfony\Component\HttpClient\HttpClient;

$zto = (new ZtoExpressGatewayFactory(HttpClient::create()))->create([
    'company_id' => getenv('ZTO_COMPANY_ID'),
    'key' => getenv('ZTO_KEY'),
    'partner_code' => getenv('ZTO_PARTNER'),
    'sandbox' => true,
]);

$shipment = new Shipment(
    new Address('张三', ['科技园南区1号', '南山区', '广东省'], '518057', '深圳市', 'CN', phone: '13800138000'),
    new Address('李四', ['建国路88号', '朝阳区', '北京市'], '100022', '北京市', 'CN', phone: '13900139000'),
    [new Parcel(1200)],
    reference: 'CMD-1042',
    options: ['description' => '印刷品'],
);

$label = $zto->ship($shipment);
$printData = json_decode((string) $label->content, true);   // for the shop's own waybill template
echo $label->trackingNumber, ' ', $label->trackingUrl, "\n";

$tracking = $zto->track($label->trackingNumber);
echo $tracking->status->value, ' ', $tracking->latest()?->location, "\n";

var_dump($zto->cancel($label->trackingNumber));
```

In Symfony:

```yaml
omnibus:
    gateways:
        zto_express:
            factory: zto_express
            options:
                company_id: '%env(ZTO_COMPANY_ID)%'
                key: '%env(ZTO_KEY)%'
                partner_code: '%env(ZTO_PARTNER)%'
                sandbox: '%env(bool:ZTO_SANDBOX)%'
                rates:
                    - { service: standard, label: '中通快递', currency: CNY, countries: [CN], bands: { 1000: 800, 5000: 1500 } }
```

## Errors

Every failure is an `Omnibus\Exception\CarrierException` whose `carrier` is `zto_express`:

- an envelope whose `status` is not true: its `message` (else `msg`, else `ZTO refused the
  request.`), its `statusCode` (else `code`) in `carrierCode` (`S102`...);
- an HTTP status of 400 or more, or a body that is not JSON: `ZTO answered HTTP <status>.`; a
  network error: `ZTO request failed: ...`;
- `ZTO issued no bill code.` when no bill code comes back.

A missing `company_id` or `key` is an `InvalidConfigException` when the gateway is built;
`Pickup`, `GetSlip`, `Paperless`, and `Rating` without `rates`, raise
`RequestNotSupportedException` before any call.

No failure carries the key: a network error's message has them masked as `***` (the key parameters
of a URL too), its previous is a copy of the HTTP client's exception (the same class and code), and
no trace of the chain keeps its arguments, where the request's options stood ([No secret in a
failure](https://github.com/glitchr-omnisense/omnibus/blob/2.x/docs/errors.md#no-secret-in-a-failure)).

## Verified, and not

Tested offline: `Tests/ZtoExpressGatewayTest.php` (an order created with its bill code and print
data, the `x-companyId` and `x-dataDigest` headers, the partner code, the province and the district
from the street lines; tracking with its statuses and location; the cancellation; a refusal raised
with its code) and `Tests/ZtoExpressContractTest.php`, the family's conformance suite on recorded
answers. The example above was run in the core's harness on those recorded answers.

Not verified: the README and the factory mark the package **unverified** against the test
environment until an account's keys are at hand; it is built from ZTO's published open platform
documentation.

## Responsibility

An independent, unofficial client, not affiliated with ZTO Express (Cayman) Inc. Whoever runs it
complies with ZTO Express's terms (those of its open platform, accepted on registration:
[open.zto.com](https://open.zto.com/)): see the README's Responsibility section.
