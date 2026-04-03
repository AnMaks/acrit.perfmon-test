<?php

namespace Acrit\Perfmon\Tables;

use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-таблица.
 */
class RunHistoryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'acrit_perfmon_run_history';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new \Bitrix\Main\ORM\Fields\DatetimeField('CREATED_AT', [
                'default_value' => static function () {
                    return new DateTime();
                },
            ]),
            new IntegerField('USER_ID'),
            new StringField('SUMMARY', [
                'required' => true,
            ]),
            new TextField('REPORT_TEXT', [
                'required' => true,
            ]),
            new IntegerField('TOTAL_COUNT', [
                'default_value' => 0,
            ]),
            new IntegerField('SUCCESS_COUNT', [
                'default_value' => 0,
            ]),
            new IntegerField('FAIL_COUNT', [
                'default_value' => 0,
            ]),
        ];
    }
}
