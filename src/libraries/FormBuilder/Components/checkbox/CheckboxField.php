<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\checkbox;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class CheckboxField extends BaseField
{
    protected array $options    = [];
    protected array $attributes = [
        'class' => 'form-check-input',
    ];

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Return value sebagai comma-separated string untuk Alpine.
     */
    public function getAlpineValue(): mixed
    {
        $value = $this->resolveValue();

        if (is_array($value)) {
            return implode(',', $value);
        }

        return is_string($value) ? $value : '';
    }

    public function render(): string
    {
        $errorHtml = $this->hasError()
            ? "<div class=\"invalid-feedback d-block\">{$this->getError()}</div>"
            : '';

        $inputHtml = '';
        foreach ($this->options as $key => $label) {
            $id  = str_replace(['[', ']'], ['__', ''], $this->name . '_' . $key);
            $val = esc((string) $key);

            $inputHtml .= <<<HTML
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="{$id}"
                                name="{$this->name}[]"
                                value="{$val}"
                                class="form-check-input"
                                :checked="(fields.{$this->name} || '').split(',').includes('{$val}')"
                                @change="
                                    let arr = (fields.{$this->name} || '').split(',').filter(Boolean);
                                    if (\$event.target.checked) { arr.push('{$val}'); }
                                    else { arr = arr.filter(v => v !== '{$val}'); }
                                    fields.{$this->name} = arr.join(',');
                                " />

                            <label class="form-check-label" for="{$id}">
                                {$label}
                            </label>
                        </div>
                HTML;
        }

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <div class="mt-1">
                        {$inputHtml}
                    </div>
                    {$errorHtml}
                </div>
            HTML;
    }
}
