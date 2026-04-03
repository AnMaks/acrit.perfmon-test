<?php

namespace Acrit\Perfmon\Tests;

interface TestInterface
{
    public function getCode(): string;
    public function getTitle(): string;
    public function getGroup(): string;
    public function getSort(): int;
    public function getDescription(): string;
    public function getRecommendation(): string;
    public function run(): TestResult;
}
