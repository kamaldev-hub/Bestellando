<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data; // Data to validate, typically $_POST or JSON input
    }

    public function validate(array $data, array $rulesSet): bool
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rulesSet as $field => $rules) {
            $value = $this->data[$field] ?? null;
            foreach ($rules as $ruleName => $ruleValue) {
                // Rule can be simple 'required' or 'rule:param'
                $params = [];
                if (is_string($ruleName) && str_contains($ruleName, ':')) {
                    list($ruleName, $paramStr) = explode(':', $ruleName, 2);
                    $params = explode(',', $paramStr);
                } elseif(is_int($ruleName)) { // handles ['required', 'email']
                    $ruleName = $ruleValue;
                    $ruleValue = true; // for rules like 'required' that don't need a param
                }


                $methodName = 'validate' . ucfirst($ruleName);
                if (method_exists($this, $methodName)) {
                    if (!$this->$methodName($field, $value, ...$params)) {
                        // Stop validating this field on first error, or collect all errors?
                        // For now, let's stop on first error for a field.
                        break;
                    }
                } else {
                    // Log or throw exception for unknown validation rule
                    error_log("Unknown validation rule: {$ruleName}");
                }
            }
        }
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // --- Individual Validation Methods ---

    protected function validateRequired(string $field, $value): bool
    {
        if (is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field is required.");
            return false;
        }
        return true;
    }

    protected function validateEmail(string $field, $value): bool
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field must be a valid email address.");
            return false;
        }
        return true;
    }

    protected function validateMinLength(string $field, $value, string $length): bool
    {
        $len = (int)$length;
        if (!empty($value) && mb_strlen((string)$value) < $len) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field must be at least {$len} characters.");
            return false;
        }
        return true;
    }

    protected function validateMaxLength(string $field, $value, string $length): bool
    {
        $len = (int)$length;
        if (!empty($value) && mb_strlen((string)$value) > $len) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field may not be greater than {$len} characters.");
            return false;
        }
        return true;
    }

    protected function validateNumeric(string $field, $value): bool
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field must be a number.");
            return false;
        }
        return true;
    }

    protected function validateInteger(string $field, $value): bool
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field must be an integer.");
            return false;
        }
        return true;
    }

    protected function validateAlphaNum(string $field, $value): bool
    {
        if (!empty($value) && !ctype_alnum((string)$value)) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field may only contain letters and numbers.");
            return false;
        }
        return true;
    }

    protected function validateIn(string $field, $value, ...$allowedValues): bool
    {
        if (!empty($value) && !in_array($value, $allowedValues, true)) {
            $this->addError($field, "The selected {$this->friendlyFieldName($field)} is invalid. Allowed values: " . implode(', ', $allowedValues));
            return false;
        }
        return true;
    }

    protected function validateUrl(string $field, $value): bool
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$this->friendlyFieldName($field)} field must be a valid URL.");
            return false;
        }
        return true;
    }

    protected function validateDecimal(string $field, $value, string $maxDigits = '10', string $decimalPlaces = '2'): bool
    {
        if (!empty($value)) {
            $pattern = "/^(?=.*\d)\d{1," . ($maxDigits - (int)$decimalPlaces) . "}(\.\d{1," . $decimalPlaces . "})?$/";
            if (!preg_match($pattern, (string)$value)) {
                 $this->addError($field, "The {$this->friendlyFieldName($field)} field must be a valid decimal number (e.g., 123.45). Max {$maxDigits} digits, {$decimalPlaces} decimal places.");
                return false;
            }
        }
        return true;
    }


    // --- Helper Methods ---

    protected function friendlyFieldName(string $field): string
    {
        return str_replace(['_', '-'], ' ', ucfirst($field));
    }

    /**
     * Get the validated data, optionally only a specific field.
     * @param string|null $key The specific field to retrieve. If null, returns all data.
     * @return mixed The value of the field or array of all data. Null if field not found.
     */
    public function getValidatedData(string $key = null): mixed
    {
        if ($key === null) {
            return $this->data; // Potentially only return fields that were actually validated?
        }
        return $this->data[$key] ?? null;
    }
}
