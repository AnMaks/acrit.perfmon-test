<?php

namespace Acrit\Perfmon\Tests;

/**
 * Содержит общие утилиты и сборку объекта результата.
 */
abstract class AbstractTest implements TestInterface
{
    protected function createResult(bool $success, string $statusText, string $message = '', array $meta = []): TestResult
    {
        return new TestResult([
            'CODE' => $this->getCode(),
            'TITLE' => $this->getTitle(),
            'GROUP' => $this->getGroup(),
            'SUCCESS' => $success,
            'STATUS_TEXT' => $statusText,
            'MESSAGE' => $message,
            'DESCRIPTION' => $this->getDescription(),
            'RECOMMENDATION' => $this->getRecommendation(),
            'META' => $meta,
            'SORT' => $this->getSort(),
        ]);
    }

    protected function iniBool(string $name): bool
    {
        $value = strtolower((string)ini_get($name));
        return in_array($value, ['1', 'on', 'yes', 'true'], true);
    }

}
