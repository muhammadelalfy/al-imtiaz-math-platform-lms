<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InstalledModule */
class InstalledModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plugin_product_id' => $this->plugin_product_id,
            'module_name' => $this->module_name,
            'version' => $this->version,
            'status' => $this->status,
            'config' => $this->config,
            'installed_at' => $this->installed_at,
            'last_error' => $this->last_error,
            'plugin' => $this->whenLoaded('plugin', fn () => new PluginProductResource($this->plugin)),
        ];
    }
}
