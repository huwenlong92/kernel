<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Larkit\Kernel\Messages;

/**
 * Class Image.
 *
 * @property string $media_id
 */
class File extends Media
{
    /**
     * @var string
     */
    protected $type = 'file';
}
