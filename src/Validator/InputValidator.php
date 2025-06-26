<?php
namespace MCPPlugin\Validator;

defined('_JEXEC') or die;

class InputValidator
{
    public static function validateInt($value, $default = 0, $min = null, $max = null)
    {
        if (!is_numeric($value)) {
            return $default;
        }
        $intValue = (int) $value;
        if ($min !== null && $intValue < $min) {
            return $default;
        }
        if ($max !== null && $intValue > $max) {
            return $default;
        }
        return $intValue;
    }

    public static function validateString($value, $default = '', $maxLength = null)
    {
        if (!is_string($value)) {
            return $default;
        }
        $trimmed = trim($value);
        if ($maxLength !== null && strlen($trimmed) > $maxLength) {
            return substr($trimmed, 0, $maxLength);
        }
        return $trimmed;
    }

    public static function validateRequired($data, $requiredFields)
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || 
                (is_string($data[$field]) && trim($data[$field]) === '') ||
                (is_array($data[$field]) && empty($data[$field]))) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    public static function validateState($state)
    {
        $validStates = [1, 0, 2, -2];
        if (!is_numeric($state)) {
            return null;
        }
        $intState = (int) $state;
        return in_array($intState, $validStates) ? $intState : null;
    }

    public static function validateOrderDirection($direction)
    {
        $direction = strtoupper(trim($direction));
        return in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
    }

    public static function validateOrderField($field, $allowedFields, $default = 'id')
    {
        $field = trim($field);
        return in_array($field, $allowedFields) ? $field : $default;
    }

    public static function sanitizeHtml($html)
    {
        return trim($html);
    }

    public static function validatePagination($data)
    {
        return [
            'limit' => self::validateInt($data['limit'] ?? 0, 0, 0, 1000),
            'offset' => self::validateInt($data['offset'] ?? 0, 0, 0)
        ];
    }
}
