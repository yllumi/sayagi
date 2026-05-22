<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Traits;

trait FieldResolverTrait
{
    /**
     * Maps YAML 'form' type aliases to actual component directory names.
     * Needed when the type name is a PHP reserved keyword (e.g. 'switch').
     */
    protected array $typeAliases = [
        'switch' => 'switcher',
    ];

    protected function toClassName(string $type): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $type)));
    }

    protected function resolveFieldClass(string $type): string
    {
        $resolved  = $this->typeAliases[$type] ?? $type;
        $className = $this->toClassName($resolved) . 'Field';

        return "\\Yllumi\\Sayagi\\libraries\\FormBuilder\\Components\\{$resolved}\\{$className}";
    }
}
