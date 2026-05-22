<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\color;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class ColorField extends BaseField
{
    protected string $type = 'color';

    public function render(): string
    {
        $id = str_replace(['[', ']'], ['__', ''], $this->name);

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <div class="input-group" style="max-width:260px">
                        <input
                            type="color"
                            id="{$id}_picker"
                            :value="fields.{$this->name} || '#000000'"
                            class="form-control form-control-color p-1"
                            style="width:48px;cursor:pointer"
                            @input="fields.{$this->name} = \$event.target.value" />
                        <input
                            type="text"
                            id="{$id}"
                            name="{$this->name}"
                            x-model="fields.{$this->name}"
                            placeholder="#000000"
                            class="form-control" />
                    </div>
                </div>
            HTML;
    }
}
