<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\mask;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class MaskField extends BaseField
{
    protected string $type      = 'mask';
    protected array $attributes = [
        'class' => 'form-control',
    ];

    public function render(): string
    {
        $id    = str_replace(['[', ']'], ['__', ''], $this->name);
        $name  = esc($this->name);
        $value = esc($this->resolveValue());

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <div class="input-group">
                        <input
                            type="password"
                            id="{$id}"
                            name="{$name}"
                            x-model="fields.{$this->name}"
                            class="form-control"
                            autocomplete="off" />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            onclick="(function(btn){var inp=btn.previousElementSibling;inp.type=inp.type==='password'?'text':'password';btn.querySelector('i').className=inp.type==='password'?'bi bi-eye':'bi bi-eye-slash';})(this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            HTML;
    }
}
