<?php

namespace Yllumi\Sayagi\libraries\FormBuilder;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;
use Yllumi\Sayagi\libraries\FormBuilder\Traits\FieldResolverTrait;
use InvalidArgumentException;
use RuntimeException;

class FormBuilder
{
    use FieldResolverTrait;

    /**
     * @var BaseField[]
     */
    protected array $fields = [];

    /**
     * Set schema via array of field objects
     *
     * @param BaseField[] $fields
     */
    public function schema(array $fields): static
    {
        foreach ($fields as $field) {
            if (! $field instanceof BaseField) {
                throw new InvalidArgumentException('All fields must extend BaseField.');
            }

            $this->fields[$field->getName()] = $field;
        }

        return $this;
    }

    /**
     * Set schema via associative array (e.g. from YAML or database)
     */
    public function schemaArray(array $fieldSchema): static
    {
        foreach ($fieldSchema as $field) {
            if (! isset($field['type'])) {
                continue; // Skip non-fillable fields
            }

            $type  = $field['type'] ?? 'text';
            $class = $this->resolveFieldClass($type);

            if (! class_exists($class)) {
                throw new RuntimeException("Field type '{$type}' not found for field '{$field['name']}'. \nMake sure the class exists: {$class}");
            }

            if (! method_exists($class, 'fromArray')) {
                throw new RuntimeException("Field class must implement static fromArray(): {$class}");
            }

            $this->fields[$field['name']] = $class::fromArray($field);
        }

        return $this;
    }

    /**
     * Render all fields to HTML
     *
     * @param mixed|null $values
     */
    public function render($values = null): string
    {
        // First pass: set all values so toAlpineData() can be called after render()
        foreach ($this->fields as $f) {
            $f->setValue($values[$f->getName()] ?? null);
        }

        return implode("\n", array_map(
            static function (BaseField $f): string {
                $html   = $f->render();
                $xShow  = $f->getXShowExpression();
                if ($xShow !== null) {
                    $html = "<div x-show='$xShow' x-cloak>\n{$html}\n</div>";
                }
                return $html;
            },
            $this->fields
        ));
    }

    /**
     * Return Alpine-compatible initial values keyed by field name.
     * Call after render() so resolved values are already populated.
     */
    public function toAlpineData(): array
    {
        $data = [];
        foreach ($this->fields as $name => $field) {
            $data[$name] = $field->getAlpineValue();
        }

        return $data;
    }

    /**
     * JSON-encoded version of toAlpineData() for inline x-data injection.
     */
    public function toAlpineJson(): string
    {
        return json_encode(
            $this->toAlpineData(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Render one field to HTML
     */
    public function renderField(string $name): string
    {
        if (! isset($this->fields[$name])) {
            throw new InvalidArgumentException("Field '{$name}' tidak ditemukan di form.");
        }

        return $this->fields[$name]->render();
    }

    /**
     * Get all validation rules
     */
    public function getValidationRules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $fieldRules = $field->getRules();
            if (! empty($fieldRules)) {
                $rules[$field->getName()] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Get all validation messages
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->fields as $field) {
            $fieldMessages = $field->getErrorMessages();

            if (! empty($fieldMessages)) {
                $messages[$field->getName()] = $fieldMessages;
            }
        }

        return $messages;
    }

    public function applyValidationErrors(array $errors): static
    {
        foreach ($this->fields as $field) {
            $name = $field->getName();

            if (isset($errors[$name])) {
                $field->setError($errors[$name]);
            }
        }

        return $this;
    }
}
