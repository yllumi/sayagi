<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\select;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;
use support\Db;

class SelectField extends BaseField
{
    protected array $options    = [];
    protected array $relation   = [];
    protected array $attributes = [
        'class' => 'form-select',
    ];

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function setRelation(array $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    public function render(): string
    {
        $id       = str_replace(['[', ']'], ['__', ''], $this->name);
        $selected = $this->resolveValue();

        // Tambahkan required dan caption jika perlu
        if (! empty($this->rules) && str_contains($this->rules, 'required')) {
            $this->attributes['required'] ??= true;
        }

        if (! empty($this->label)) {
            $this->attributes['data-caption'] ??= $this->label;
        }

        $attrHtml    = $this->renderAttributes();
        $optionsHtml = $this->generateOptions($selected);
        $errorHtml   = $this->hasError()
            ? "<div class=\"invalid-feedback d-block\">{$this->getError()}</div>"
            : '';

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <select id="{$id}" name="{$this->name}" x-model="fields.{$this->name}" {$attrHtml}>
                        {$optionsHtml}
                    </select>
                    {$errorHtml}
                </div>
            HTML;
    }

    private function generateOptions(string $selected = ''): string
    {
        $optionsHtml = '<option value="">-pilih opsi-</option>';

        // If select use attribute options
        if ($this->options) {
            foreach ($this->options as $key => $label) {
                $isSelected = ($key === $selected) ? 'selected' : '';
                $optionsHtml .= '<option value="' . esc($key) . "\" {$isSelected}>" . esc($label) . '</option>';
            }
        }

        // If select use relation
        elseif ($this->relation && !empty($this->relation['table'])) {
            $fk      = $this->relation['foreignKey'];
            $display = $this->relation['display'];
            $builder = Db::table($this->relation['table']);
            $builder->select([$display, $this->relation['value'] . ' AS ' . $fk]);
            $options = $builder->get();

            foreach ($options as $option) {
                $val        = (string) $option->{$fk};
                $isSelected = ($val === (string) $selected) ? 'selected' : '';
                $optionsHtml .= '<option value="' . esc($val) . "\" {$isSelected}>" . esc($option->{$display}) . '</option>';
            }
        }

        return $optionsHtml;
    }
}
