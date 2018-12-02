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

namespace Ellis\Jaeger;

final class Constants
{
    const SAMPLER_TYPE_TAG_KEY = 'sampler.type';
    const SAMPLER_PARAM_TAG_KEY = 'sampler.param';

    const DEBUG_TAG_KEY = 'jaeger-debug-id';
}
