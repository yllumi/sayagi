<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\textarea;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class TextareaField extends BaseField
{
    protected array $attributes = [
        'class' => 'form-control',
        'rows'  => 5,
    ];

    public function render(): string
    {
        $id    = str_replace(['[', ']'], ['__', ''], $this->name);
        $value = esc($this->resolveValue());

        // Tambah required jika ada di rules
        if (! empty($this->rules) && str_contains($this->rules, 'required')) {
            $this->attributes['required'] = true;
        }

        // Tambah data-caption dari label
        if (! empty($this->label)) {
            $this->attributes['data-caption'] = $this->label;
        }

        $attrHtml  = $this->renderAttributes();
        $errorHtml = $this->hasError()
            ? "<div class=\"invalid-feedback d-block\">{$this->getError()}</div>"
            : '';

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <textarea
                        id="{$id}"
                        name="{$this->name}"
                        x-model="fields.{$this->name}"
                        {$attrHtml}></textarea>
                    {$errorHtml}
                </div>
            HTML;
    }
}
