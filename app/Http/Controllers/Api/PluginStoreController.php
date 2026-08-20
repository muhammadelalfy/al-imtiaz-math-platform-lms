<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\PluginStoreRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\InstalledModuleResource;
use App\Http\Resources\PluginPurchaseResource;
use App\Http\Resources\PluginProductResource;
use App\Models\PluginProduct;
use App\Services\PluginInstaller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class PluginStoreController extends Controller
{
    public function __construct(private readonly PluginStoreRepositoryInterface $plugins)
    {
    }

    public function index(Request $request)
    {
        return response()->json([
            'data' => PluginProductResource::collection($this->plugins->catalogFor($request->user()))->resolve($request),
        ]);
    }

    public function installed()
    {
        return response()->json([
            'data' => InstalledModuleResource::collection($this->plugins->installed())->resolve(),
        ]);
    }

    public function purchase(Request $request, PluginProduct $plugin): JsonResponse
    {
        abort_unless($plugin->is_active, 404);
        $purchase = $this->plugins->purchase($request->user(), $plugin);

        return response()->json(
            ['purchase' => (new PluginPurchaseResource($purchase))->resolve($request)],
            $purchase->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function install(Request $request, PluginProduct $plugin, PluginInstaller $installer): JsonResponse
    {
        abort_unless($request->user()->isAnyRole('admin'), 403, 'تثبيت الإضافات متاح لمدير النظام فقط.');
        abort_unless($plugin->is_active, 404);
        abort_unless($this->plugins->hasCompletedPurchase($request->user(), $plugin), 403, 'يجب شراء الإضافة قبل تثبيتها.');

        try {
            $module = $installer->install($plugin, $request->user()->id)->load('plugin');

            return response()->json([
                'module' => (new InstalledModuleResource($module))->resolve($request),
                'message' => 'تم تثبيت الإضافة وتفعيلها بنجاح.',
            ]);
        } catch (Throwable $error) {
            report($error);

            return response()->json(['message' => $error->getMessage()], 422);
        }
    }

    public function uninstall(Request $request, PluginProduct $plugin): JsonResponse
    {
        abort_unless($request->user()->isAnyRole('admin'), 403, 'إدارة الإضافات متاحة لمدير النظام فقط.');
        $module = $this->plugins->installedModuleFor($plugin);
        $path = base_path($module->path);

        if (is_dir($path)) {
            abort_unless(str_starts_with(realpath($path) ?: '', realpath(base_path('Modules')) . DIRECTORY_SEPARATOR), 422, 'مسار الوحدة غير آمن.');
            File::deleteDirectory($path);
        }

        $this->plugins->markUninstalled($module);

        return response()->json(['message' => 'تم إلغاء تثبيت الإضافة.']);
    }
}
