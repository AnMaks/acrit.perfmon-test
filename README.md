# acrit.perfmon-test


## 1. Создать файл теста
Папка:

```php
/local/modules/acrit.perfmon/lib/tests/items/
```

---

## 2. Создть класс теста
Пример:

```php
<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Acrit\Perfmon\Tests\TestResult;

class Mytest extends AbstractTest
{
    public function getCode(): string
    {
        return 'MY_TEST';
    }

    public function getTitle(): string
    {
        return 'Мой тест';
    }

    public function getGroup(): string
    {
        return 'PHP';
    }

    public function getSort(): int
    {
        return 500;
    }

    public function getDescription(): string
    {
        return 'Описание теста';
    }

    public function getRecommendation(): string
    {
        return 'Что сделать если есть проблема';
    }

    public function run(): TestResult
    {
        $success = true;

        return $this->createResult(
            $success,
            $success ? 'Успешно' : 'Проблема',
            'Тест выполнен'
        );
    }
}
```

---

## 3. Зарегистрировать тест
Открой файл:

```php
/local/modules/acrit.perfmon/lib/tests/registry.php
```

Добавить класс в список:

```php
Mytest::class,
```

И `use` сверху:

```php
use Acrit\Perfmon\Tests\Items\Mytest;
```
---

## Как это работает
У каждого теста есть:
- `getCode()` — код теста;
- `getTitle()` — название;
- `getGroup()` — группа;
- `getSort()` — порядок вывода;
- `getDescription()` — описание;
- `getRecommendation()` — рекомендация;
- `run()` — сама проверка.

Главная логика всегда находится в:

```php
run(): TestResult
```

Именно там условие проверки и результат.

---

## Самая короткая схема
1. Создать файл в `lib/tests/items/`
2. Написать класс теста
3. Добавить его в `registry.php`
4. Очистить кеш
5. Проверить на странице модуля
