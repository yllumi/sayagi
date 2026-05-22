<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\switcher;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

/**
 * SwitcherField – handles YAML `form: switch`.
 * Named "switcher" because "switch" is a PHP reserved keyword and cannot be
 * used as a namespace segment.
 */
class SwitcherField extends BaseField
{
    protected string $type   = 'switcher';
    protected array $options = [];

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function render(): string
    {
        $name = esc($this->name);

        // Bootstrap toggle switch: exactly 2 options with key '0' as the off value
        $keys = array_keys($this->options);
        if (count($this->options) === 2 && in_array('0', array_map('strval', $keys))) {
            $onValue = '';
            $onLabel = '';

            foreach ($this->options as $k => $v) {
                if ((string) $k !== '0') {
                    $onValue = esc((string) $k);
                    $onLabel = esc((string) $v);
                }
            }

            return <<<HTML
                    <div class="form-group mb-3">
                        {$this->renderLabel()}
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check form-switch mb-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="{$name}_switch"
                                    name="{$name}"
                                    x-model="fields.{$this->name}"
                                    :true-value="'{$onValue}'"
                                    :false-value="'0'">
                                <label class="form-check-label" for="{$name}_switch">
                                    {$onLabel}
                                </label>
                            </div>
                        </div>
                    </div>
                HTML;
        }

        // Radio group for > 2 options
        $radiosHtml = '';
        foreach ($this->options as $k => $v) {
            $k           = esc((string) $k);
            $v           = esc((string) $v);
            $radiosHtml .= <<<HTML
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                name="{$name}" id="{$name}_{$k}" value="{$k}"
                                x-model="fields.{$this->name}">
                            <label class="form-check-label" for="{$name}_{$k}">{$v}</label>
                        </div>
                    HTML;
        }

        return <<<HTML
                <div class="form-group mb-3">
                    {$this->renderLabel()}
                    <div class="mt-1">
                        {$radiosHtml}
                    </div>
                </div>
            HTML;
    }
}
