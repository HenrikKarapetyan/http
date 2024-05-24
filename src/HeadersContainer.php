<?php

namespace Henrik\Http;

use Henrik\Container\Container;
use Henrik\Container\ContainerModes;

class HeadersContainer extends Container
{
    public function __construct()
    {
        $this->changeMode(ContainerModes::MULTIPLE_VALUE_MODE);
    }
}