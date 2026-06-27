<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\date;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class DateField extends BaseField
{
    protected string $type      = 'text'; // input visible tetap type="text"
    protected string $format    = 'DD/MM/YYYY';
    protected string $dbFormat  = 'YYYY-MM-DD';
    protected array $attributes = [
        'class'        => 'form-control',
        'autocomplete' => 'off',
        'data-toggle'  => 'datepicker',
    ];

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function setDbFormat(string $format): static
    {
        $this->dbFormat = $format;

        return $this;
    }

    public function render(): string
    {
        $id = str_replace(['[', ']'], ['__', ''], $this->name);

        // Required rule
        if (! empty($this->rules) && str_contains($this->rules, 'required')) {
            $this->attributes['required'] = true;
        }

        $attrHtml  = $this->renderAttributes();
        $errorHtml = $this->hasError()
            ? "<div class=\"invalid-feedback d-block\">{$this->getError()}</div>"
            : '';

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <input
                        type="text"
                        id="{$id}"
                        data-date-field
                        data-date-format="{$this->format}"
                        data-db-format="{$this->dbFormat}"
                        data-field-name="{$this->name}"
                        placeholder="{$this->format}"
                        {$attrHtml} />

                    <input type="hidden"
                           name="{$this->name}"
                           x-model="fields.{$this->name}" />

                    <small class="form-text text-muted d-block mt-1"
                           x-show="fields.{$this->name}"
                           x-text="moment(fields.{$this->name}, '{$this->dbFormat}').isValid()
                               ? moment(fields.{$this->name}, '{$this->dbFormat}').format('DD MMMM YYYY')
                               : ''">
                    </small>

                    {$errorHtml}
                </div>
            HTML;
    }
}
