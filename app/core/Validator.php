<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);

            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleParam = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }

                $method = 'rule' . ucfirst($ruleName);

                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $ruleParam);
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function ruleRequired(string $field, mixed $value, mixed $param = null): void
    {
        $empty = $value === null || $value === '' || (is_array($value) && count($value) === 0);

        if ($empty) {
            $this->addError($field, 'This field is required.');
        }
    }

    private function ruleMin(string $field, mixed $value, mixed $param = null): void
    {
        $min = (int) $param;

        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "Minimum length is {$min}.");
        }

        if (is_numeric($value) && (float) $value < $min) {
            $this->addError($field, "Minimum value is {$min}.");
        }

        if (is_array($value) && count($value) < $min) {
            $this->addError($field, "Select at least {$min} option(s).");
        }
    }

    private function ruleMax(string $field, mixed $value, mixed $param = null): void
    {
        $max = (int) $param;

        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "Maximum length is {$max}.");
        }

        if (is_numeric($value) && (float) $value > $max) {
            $this->addError($field, "Maximum value is {$max}.");
        }
    }

    private function ruleNumeric(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_numeric($value)) {
            $this->addError($field, 'Must be a numeric value.');
        }
    }

    private function ruleInteger(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, 'Must be an integer value.');
        }
    }

    private function ruleDate(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $date = \DateTime::createFromFormat('Y-m-d', (string) $value);
        $errors = \DateTime::getLastErrors();

        if (!$date || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            $this->addError($field, 'Invalid date format.');
        }
    }

    private function ruleEmail(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email format.');
        }
    }

    private function ruleIn(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $allowed = array_map('trim', explode(',', (string) $param));

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!in_array((string) $item, $allowed, true)) {
                    $this->addError($field, 'Contains an invalid option.');
                    return;
                }
            }

            return;
        }

        if (!in_array((string) $value, $allowed, true)) {
            $this->addError($field, 'Contains an invalid option.');
        }
    }

    private function ruleArray(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_array($value)) {
            $this->addError($field, 'Must be an array of values.');
        }
    }

    private function ruleRegex(string $field, mixed $value, mixed $param = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $pattern = '/' . str_replace('/', '\/', (string) $param) . '/';

        if (!preg_match($pattern, (string) $value)) {
            $this->addError($field, 'Invalid format.');
        }
    }
}
