<?php

namespace App\Support;

class RequestGuard
{
    public static function sort($sort, array $allowedColumns, $defaultColumn = 'created_at', $defaultDirection = 'desc')
    {
        $parts     = explode('-', (string) $sort, 2);
        $column    = $parts[0] ?? '';
        $direction = strtolower($parts[1] ?? '');

        if(!in_array($column, $allowedColumns, true))
        {
            $column = $defaultColumn;
        }

        if(!in_array($direction, ['asc', 'desc'], true))
        {
            $direction = $defaultDirection;
        }

        return [$column, $direction];
    }

    public static function allowedView($view, array $allowed, $fallback)
    {
        $view = (string) $view;

        if(!in_array($view, $allowed, true))
        {
            return $fallback;
        }

        return $view;
    }

    public static function likePrefix($keyword)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $keyword);
    }

    public static function safeLangCode($code)
    {
        $code = strtolower((string) $code);

        return preg_match('/^[a-z]{2}([_-][a-z]{2})?$/', $code) ? $code : null;
    }

    public static function invoiceTemplates()
    {
        return [
            'template1',
            'template2',
            'template3',
            'template4',
            'template5',
            'template6',
            'template7',
            'template8',
            'template9',
            'template10',
        ];
    }

    public static function safeInvoiceTemplate($template)
    {
        return in_array($template, self::invoiceTemplates(), true) ? $template : 'template1';
    }

    public static function hexColor($color)
    {
        $color = ltrim((string) $color, '#');

        return preg_match('/^[0-9a-fA-F]{3,8}$/', $color) ? $color : null;
    }

    public static function idList($value)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $value)));

        return implode(',', $ids);
    }
}
