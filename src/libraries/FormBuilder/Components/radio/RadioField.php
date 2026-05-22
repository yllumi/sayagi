<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\radio;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class RadioField extends BaseField
{
    protected array $options = [];

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function render(): string
    {
        if (! empty($this->rules) && str_contains($this->rules, 'required')) {
            $this->attributes['required'] ??= true;
        }

        $errorHtml = $this->hasError()
            ? "<div class=\"invalid-feedback d-block\">{$this->getError()}</div>"
            : '';

        $optionsHtml = '';
        foreach ($this->options as $value => $label) {
            $id           = $this->name . '_' . $value;
            $optionsHtml .= <<<HTML
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                id="{$id}" value="{$value}"
                                x-model="fields.{$this->name}">
                            <label class="form-check-label" for="{$id}">{$label}</label>
                        </div>
            HTML;
        }

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <div class="mt-1">
                        {$optionsHtml}
                    </div>
                    {$errorHtml}
                </div>
            HTML;
    }
}
