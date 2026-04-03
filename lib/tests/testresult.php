<?php

namespace Acrit\Perfmon\Tests;

class TestResult
{
    protected array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return !empty($this->data['SUCCESS']);
    }
}
