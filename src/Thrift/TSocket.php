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

namespace Ellis\Jaeger\Thrift;

use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TSocket as BaseTSocket;

class TSocket extends BaseTSocket
{
    private $maxPacketSize;

    public function __construct(
        string $host,
        int $port,
        int $maxPacketSize,
        bool $persist = false,
        ?callable $debugHandler = null
    ) {
        parent::__construct($host, $port, $persist, $debugHandler);
        $this->maxPacketSize = $maxPacketSize;
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        if ($this->isOpen()) {
            throw new TTransportException('Socket already connected', TTransportException::ALREADY_OPEN);
        }

        if (empty($this->host_)) {
            throw new TTransportException('Cannot open null host', TTransportException::NOT_OPEN);
        }

        if ($this->port_ <= 0) {
            throw new TTransportException('Cannot open without port', TTransportException::NOT_OPEN);
        }

        if ($this->persist_) {
            $this->handle_ = @pfsockopen(
                $this->host_,
                $this->port_,
                $errno,
                $errstr,
                $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000)
            );
        } else {
            $this->handle_ = @fsockopen(
                $this->host_,
                $this->port_,
                $errno,
                $errstr,
                $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000)
            );
        }

        if ($this->handle_ === false) {
            $error = 'TSocket: Could not connect to '.$this->host_.':'.$this->port_.' ('.$errstr.' ['.$errno.'])';

            throw new TException($error);
        }

        stream_set_chunk_size($this->handle_, $this->maxPacketSize);
    }
}
