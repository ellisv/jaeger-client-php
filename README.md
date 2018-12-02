# Jaeger Bindings for PHP OpenTracing API

Instrumentation library that implements an [OpenTracing](http://opentracing.io)
Tracer for Jaeger (https://jaegertracing.io).

## Installation

```bash
composer require ellis/jaeger-client
```

## Initialization

```php
use Ellis\Jaeger\Reporter\RemoteReporter;
use Ellis\Jaeger\Sampler\ConstSampler;
use Ellis\Jaeger\Tracer;
use Ellis\Jaeger\Transport\UdpTransport;
use OpenTracing\GlobalTracer;

$transport = new UdpTransport('127.0.0.1', 6831);
$reporter = new RemoteReporter($transport);
$sampler = new ConstSampler(true);
$tracer = new Tracer('my-service', $reporter, $sampler);

// Other libraries may try to get open tracing implementation from GlobalTracer
// so setting your tracer instance to GlobalTracer is a good practice.
GlobalTracer::set($tracer);

// Make sure we gracefully close Tracer on any situation
register_shutdown_function(function (Tracer $tracer, LoggerInterface $logger) {
    try {
        @$tracer->close();
    } catch (\Exception $e) {
        $logger->warn('Failed closing Tracer: '.$e->getMessage());
    }
}, $tracer, $logger);
```

## License

[The MIT License](LICENSE).
