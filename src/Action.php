<?php

namespace tomasz\s2examples;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Action
{
    public function run(ServerRequestInterface $request): ResponseInterface;
}