<?php

namespace Omnibus\ZtoExpress\Tests;

use Omnibus\GatewayFactoryInterface;
use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Omnibus\ZtoExpress\ZtoExpressGatewayFactory;
use Omnibus\Testing\GatewayContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** The conformance suite on ZTO's recorded answers (the test environment; unverified API shape); no rating API, so no rate without the "rates" option. */
final class ZtoExpressContractTest extends TestCase
{
    use GatewayContractTests;

    protected function gatewayFactory(HttpClientInterface $http): GatewayFactoryInterface
    {
        return new ZtoExpressGatewayFactory($http);
    }

    protected function gatewayOptions(): array
    {
        return ['company_id' => 'glitchr', 'key' => 'k', 'partner_code' => 'P001', 'sandbox' => true];
    }

    /** An electronic waybill: the bill code and the marks to print it with, no document from ZTO. */
    protected function issuesDocuments(): bool
    {
        return false;
    }

    protected function contractShipment(): Shipment
    {
        return new Shipment(new Address('张三', ['深南大道1000号', '福田区', '广东省'], '518000', '深圳市', 'CN', phone: '13800000000'), new Address('李四', ['建国路88号', '朝阳区', '北京市'], '100022', '北京市', 'CN', phone: '13900000000'), [new Parcel(1500, 30, 20, 10, 20000, 'CNY')], reference: 'ORDER-1042');
    }

    protected function contractTrackingNumber(): string
    {
        return '75123456789012';
    }

    protected function recordedAnswers(): array
    {
        return [
            '/zto.open.createOrder' => ['status' => true, 'result' => ['orderCode' => 'ORDER-1042', 'billCode' => '75123456789012', 'printData' => ['bigMark' => '北京', 'mark' => '朝阳']]],
            '/zto.open.getTraceInfo' => ['status' => true, 'result' => [[['billCode' => '75123456789012', 'scanType' => '签收', 'scanDate' => '2026-10-02 11:30:00', 'scanSite' => '北京朝阳', 'desc' => '快件已签收'], ['billCode' => '75123456789012', 'scanType' => '收件', 'scanDate' => '2026-10-01 17:00:00', 'scanSite' => '深圳福田', 'desc' => '快件已收件']]]],
            '/zto.open.cancelOrder' => ['status' => true, 'result' => true],
        ];
    }
}
