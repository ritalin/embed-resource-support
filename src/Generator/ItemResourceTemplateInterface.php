<?php

namespace Embed\Sample\Generator;

use BEAR\Resource\ResourceObject;

interface ItemResourceTemplateInterface {
    /**
     * @param mixed value
     * @return ResourceObject
     */
    function asResource($value);
}
