<?php

namespace Yllumi\Sayagi\libraries\FormBuilder\Components\image;

use Yllumi\Sayagi\libraries\FormBuilder\Components\BaseField;

class ImageField extends BaseField
{
    protected string $type      = 'image';
    protected array $attributes = [
        'class' => 'form-control',
    ];

    public function render(): string
    {
        $id = str_replace(['[', ']'], ['__', ''], $this->name);

        return <<<HTML
                <div class="form-group">
                    {$this->renderLabel()}
                    <input
                        type="text"
                        id="{$id}"
                        name="{$this->name}"
                        x-model="fields.{$this->name}"
                        placeholder="URL gambar atau path"
                        class="form-control" />
                    <template x-if="fields.{$this->name}">
                        <img :src="fields.{$this->name}" class="mt-2 img-thumbnail d-block" style="max-height:80px;max-width:200px" alt="preview">
                    </template>
                </div>
            HTML;
    }
}
