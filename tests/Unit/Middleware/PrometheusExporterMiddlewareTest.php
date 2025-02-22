<?php
declare(strict_types=1);

namespace Bortoman\Tests\Unit\Middleware;

use Prometheus\Histogram;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Bortoman\Tests\BaseTestCase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Bortoman\PrometheusExporter\Middleware\PrometheusExporterMiddleware;

class PrometheusExporterMiddlewareTest extends BaseTestCase
{
    public function testTerminate()
    {
        $histogram = $this->getMockBuilder(Histogram::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['observe'])
            ->getMock();

        $histogram
            ->expects($this->once())
            ->method('observe');

        /** @var CollectorRegistry|MockObject $registry */
        $registry = $this->getMockBuilder(CollectorRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrRegisterHistogram'])
            ->getMock();

        $registry
            ->expects($this->once())
            ->method('getOrRegisterHistogram')
            ->willReturn($histogram);

        (new PrometheusExporterMiddleware($registry))
            ->terminate(new Request(), new Response());
    }

    public function testTerminateWithResponse()
    {
        /** @var PrometheusExporterMiddleware|MockObject $middleware */
        $middleware = $this->getMockBuilder(PrometheusExporterMiddleware::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['measure'])
            ->getMock();

        $middleware
            ->expects($this->once())
            ->method('measure');

        $middleware->terminate(new Request(), new Response());
    }

    public function testTerminateWithJsonResponse()
    {
        /** @var PrometheusExporterMiddleware|MockObject $middleware */
        $middleware = $this->getMockBuilder(PrometheusExporterMiddleware::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['measure'])
            ->getMock();

        $middleware
            ->expects($this->once())
            ->method('measure');

        $middleware->terminate(new Request(), new JsonResponse());
    }

    public function testRouteDetails()
    {
        /** @var PrometheusExporterMiddleware|MockObject $middleware */
        $middleware = $this->getMockBuilder(PrometheusExporterMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Request|MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['server', 'getMethod', 'getPathInfo'])
            ->getMock();

        $request
            ->expects($this->once())
            ->method('server')
            ->with('REQUEST_TIME_FLOAT')
            ->willReturn(100.0);

        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/some-path');

        /** @var Response|MockObject $request */
        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['getStatusCode'])
            ->getMock();

        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $getRouteDetailsMethod = $this->getMethod(PrometheusExporterMiddleware::class, 'getRouteDetails');

        [
            'route' => $actualRoute,
            'statusCode' => $actualStatusCode,
            'durationMs' => $actualDurationMs,
        ] = $getRouteDetailsMethod->invokeArgs($middleware, [$request, $response]);

        $this->assertEquals('GET /some-path', $actualRoute);
        $this->assertEquals(200, $actualStatusCode);
        $this->assertIsFloat($actualDurationMs);
    }

    public function testCanReturnResponse()
    {
        /** @var CollectorRegistry|MockObject $registry */
        $registry = $this->getMockBuilder(CollectorRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $middleware = new PrometheusExporterMiddleware($registry);

        /** @var Request|MockObject $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $response = new Response();
        $closure = function () use ($response) { return $response; };

        $actualResponse = $middleware->handle($request, $closure);
        $this->assertEquals($response, $actualResponse);
    }

    public function testCanReturnJsonResponse()
    {
        /** @var CollectorRegistry|MockObject $registry */
        $registry = $this->getMockBuilder(CollectorRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $middleware = new PrometheusExporterMiddleware($registry);

        /** @var Request|MockObject $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $response = new JsonResponse();
        $closure = function () use ($response) { return $response; };

        $actualResponse = $middleware->handle($request, $closure);
        $this->assertEquals($response, $actualResponse);
    }
}
