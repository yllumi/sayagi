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
                    <div id="{$id}" data-ace-field="{$this->name}" style="height:{$height}px;border:1px solid #dee2e6;border-radius:.375rem;font-size:13px"></div>
                    <textarea id="{$id}_ta" name="{$name}" x-model="fields.{$this->name}" class="d-none">{$value}</textarea>
                    <script>
                        (function() {
                            function initAce_{$id}() {
                                if (typeof ace === 'undefined') { setTimeout(initAce_{$id}, 200); return; }
                                var editorEl = document.getElementById('{$id}');
                                var editor = ace.edit('{$id}');
                                editorEl._ace = editor; // stored for post-fetch sync
                                editor.setTheme('ace/theme/chrome');
                                editor.session.setMode('ace/mode/{$mode}');
                                editor.setShowPrintMargin(false);
                                editor.setFontSize(13);
                                var ta = document.getElementById('{$id}_ta');
                                editor.setValue(ta.value, -1);
                                // Ace → Alpine: dispatch native 'input' so x-model picks up changes
                                editor.session.on('change', function() {
                                    ta.value = editor.getValue();
                                    ta.dispatchEvent(new Event('input'));
                                });
                            }
                            initAce_{$id}();
                        })();
                    </script>
                </div>
            HTML;
    }
}
