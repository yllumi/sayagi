<?php
/**
 * Reusable avatar uploader block.
 *
 * @var array  $config  url, remove_url, field, extra, name, url_current
 * @var string $url     current avatar URL (or null)
 * @var string $name    display name for the initials fallback
 */
$config = $config ?? [];
$name   = (string) ($name ?? $config['name'] ?? '');
$config = array_merge([
    'url'          => '',
    'remove_url'   => '',
    'field'        => 'avatar',
    'extra'        => [],
    'name'         => $name,
    'url_current'  => $url ?? null,
], $config);

$json = htmlspecialchars(
    json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);
?>
<div class="avatar-uploader" x-data="avatarUploader(<?= $json ?>)">
    <div class="avatar-preview"
         :class="{ 'is-dragging': dragging }"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="onDrop($event)"
         role="button"
         tabindex="0"
         @click="pick()"
         @keydown.enter.prevent="pick()"
         title="Klik atau tarik gambar ke sini">
        <template x-if="preview">
            <img :src="preview" alt="Avatar">
        </template>
        <template x-if="!preview">
            <span class="avatar-initials" x-text="initials()"><?= htmlspecialchars($name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?') ?></span>
        </template>

        <span class="avatar-uploader-busy" x-show="uploading || removing" x-cloak>
            <span class="spinner-border spinner-border-sm"></span>
        </span>
    </div>

    <div class="avatar-uploader-body">
        <div class="d-flex flex-wrap gap-2 mb-1">
            <button type="button" class="btn btn-sm btn-primary" @click="pick()" :disabled="uploading">
                <i class="bi bi-upload"></i> Pilih gambar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-avatar-adjust
                    x-show="preview" x-cloak
                    @click="adjust()" :disabled="uploading || removing" title="Sesuaikan area avatar">
                <i class="bi bi-crop"></i> Sesuaikan
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" x-show="preview" x-cloak
                    @click="remove()" :disabled="removing">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
        <p class="form-text mb-0">
            JPG, PNG, WebP, atau AVIF &middot; maksimal 3 MB &middot; atur area crop sebelum disimpan.
        </p>
    </div>

    <input type="file" x-ref="avatarFile" accept="image/jpeg,image/png,image/webp,image/avif"
           class="d-none" @change="onPick($event)">
</div>
