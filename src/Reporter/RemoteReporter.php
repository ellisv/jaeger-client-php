<?php

/*
 * This file is part of the Jaeger Client package.
 *
 * (c) Eligijus Vitkauskas <eligijusvitkauskas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ellis\Jaeger\Reporter;

use Ellis\Jaeger\Span;
use Ellis\Jaeger\Transport\TransportException;
use Ellis\Jaeger\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RemoteReporter implements ReporterInterface
{
    /**
     * @var TransportInterface
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(TransportInterface $sender, ?LoggerInterface $logger = null)
    {
        $this->sender = $sender;
        $this->logger = $logger ?: new NullLogger();
    }

    public function report(Span $span): void
    {
        try {
            $this->sender->append($span);
        } catch (TransportException $e) {
            $this->logger->error(sprintf(
                'Failed to report span "%s": %s',
                $span->getOperationName(),
                $e->getMessage()
            ));
        }
    }

    public function close(): void
    {
        $this->flush();
        $this->sender->close();
    }

    public function flush(): void
    {
        try {
            $this->sender->flush();
        } catch (TransportException $e) {
            $this->logger->error(sprintf('Failed to flush a buffer: %s', $e->getMessage()));
        }
    }
}
