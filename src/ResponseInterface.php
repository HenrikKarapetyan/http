<?php

namespace Henrik\Http;

interface ResponseInterface
{
    public function send(): bool;
}