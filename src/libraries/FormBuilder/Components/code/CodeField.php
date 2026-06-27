<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\code;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class CodeField extends BaseField
{
    protected string $type   = 'code';
    protected string $mode   = 'text';   // ace mode: json, yaml, html, css, javascript, etc.
    protected int    $height = 250;

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function render(): string
    {
        $id     = 'ace_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $this->name);
        $name   = esc($this->name);
        $value  = esc($this->resolveValue());
        $mode   = esc($this->mode);
        $height = (int) $this->height;

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <div id="{$id}" data-ace-field="{$this->name}" data-ace-mode="{$mode}" style="height:{$height}px;border:1px solid #dee2e6;border-radius:.375rem;font-size:13px"></div>
                    <textarea id="{$id}_ta" name="{$name}" x-model="fields.{$this->name}" class="d-none">{$value}</textarea>
                </div>
            HTML;
    }
}
