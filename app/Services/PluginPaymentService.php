<?php

namespace App\Services;

use App\Models\PluginPaymentMethod;
use App\Models\PluginPaymentTransaction;
use App\Models\PluginProduct;
use App\Models\PluginPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PluginPaymentService
{
    /** @var array<string, array{label: string, instructions: string}> */
    private const MANUAL_METHODS = [
        'vodafone_cash' => [
            'label' => 'فودافون كاش',
            'instructions' => 'حوّل قيمة الإضافة إلى رقم فودافون كاش الخاص بالمركز، ثم أرسل رقم العملية للمراجعة.',
        ],
        'instapay' => [
            'label' => 'إنستاباي',
            'instructions' => 'حوّل قيمة الإضافة إلى عنوان إنستاباي الخاص بالمركز، ثم أرسل الرقم المرجعي للتحويل.',
        ],
        'fawry' => [
            'label' => 'فوري',
            'instructions' => 'استخدم كود أو رقم تحصيل فوري الخاص بالمركز، ثم أرسل رقم مرجع إيصال فوري للمراجعة.',
        ],
    ];

    /** @return Collection<int, PluginPaymentMethod> */
    public function allMethods(): Collection
    {
        foreach (self::MANUAL_METHODS as $code => $defaults) {
            PluginPaymentMethod::query()->firstOrCreate(['code' => $code], $defaults);
        }

        return PluginPaymentMethod::query()->orderBy('code')->get();
    }

    /** @return Collection<int, PluginPaymentMethod> */
    public function enabledMethods(): Collection
    {
        $this->allMethods();

        return PluginPaymentMethod::query()->where('is_enabled', true)->orderBy('code')->get();
    }

    /** @param array{recipient: string|null, instructions: string|null, is_enabled: bool} $attributes */
    public function configure(string $code, array $attributes, User $administrator): PluginPaymentMethod
    {
        $defaults = self::MANUAL_METHODS[$code] ?? null;
        abort_unless($defaults !== null, 404);

        $method = PluginPaymentMethod::query()->firstOrCreate(['code' => $code], $defaults);
        $recipient = $attributes['recipient'] ?? $method->recipient;
        throw_if($attributes['is_enabled'] && blank($recipient), ValidationException::withMessages([
            'recipient' => 'أدخل رقم المحفظة أو عنوان إنستاباي قبل تفعيل وسيلة الدفع.',
        ]));
        $method->update([...$attributes, 'configured_by' => $administrator->id]);

        return $method;
    }

    public function begin(User $user, PluginProduct $plugin, string $methodCode): PluginPaymentTransaction
    {
        abort_unless($plugin->is_active, 404);
        throw_if((float) $plugin->price <= 0, ValidationException::withMessages([
            'plugin' => 'الإضافة المجانية لا تحتاج إلى عملية دفع.',
        ]));

        $method = $this->enabledMethods()->firstWhere('code', $methodCode);
        throw_if($method === null, ValidationException::withMessages([
            'payment_method' => 'وسيلة الدفع غير متاحة حالياً.',
        ]));

        $purchaseExists = PluginPurchase::query()
            ->where('user_id', $user->id)
            ->where('plugin_product_id', $plugin->id)
            ->where('status', 'completed')
            ->exists();
        throw_if($purchaseExists, ValidationException::withMessages([
            'plugin' => 'تمتلك هذه الإضافة بالفعل.',
        ]));

        $pending = PluginPaymentTransaction::query()
            ->with(['plugin', 'method'])
            ->where('user_id', $user->id)
            ->where('plugin_product_id', $plugin->id)
            ->whereIn('status', ['pending', 'submitted'])
            ->latest()
            ->first();

        return $pending ?? PluginPaymentTransaction::query()->create([
            'user_id' => $user->id,
            'plugin_product_id' => $plugin->id,
            'plugin_payment_method_id' => $method->id,
            'status' => 'pending',
            'amount' => $plugin->price,
            'currency' => 'EGP',
        ])->load(['plugin', 'method']);
    }

    public function submitReference(PluginPaymentTransaction $transaction, User $user, string $reference, ?string $note): PluginPaymentTransaction
    {
        abort_unless($transaction->user_id === $user->id, 403);
        throw_if($transaction->status !== 'pending', ValidationException::withMessages([
            'payment' => 'لا يمكن تعديل هذه العملية في حالتها الحالية.',
        ]));

        $transaction->update(['reference' => $reference, 'customer_note' => $note, 'status' => 'submitted']);

        return $transaction->fresh()->load(['plugin', 'method']);
    }

    public function approve(PluginPaymentTransaction $transaction, User $administrator, ?string $note): PluginPaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $administrator, $note): PluginPaymentTransaction {
            $locked = PluginPaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            throw_if($locked->status !== 'submitted', ValidationException::withMessages([
                'payment' => 'يمكن اعتماد عمليات الدفع المرسلة فقط.',
            ]));
            throw_if(blank($locked->reference), ValidationException::withMessages([
                'reference' => 'يلزم رقم مرجعي قبل اعتماد عملية الدفع.',
            ]));

            PluginPurchase::query()->firstOrCreate(
                ['user_id' => $locked->user_id, 'plugin_product_id' => $locked->plugin_product_id],
                ['status' => 'completed', 'purchased_at' => now()],
            );

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $administrator->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'fulfilled_at' => now(),
            ]);

            return $locked->fresh()->load(['plugin', 'method', 'user', 'reviewer']);
        });
    }

    public function reject(PluginPaymentTransaction $transaction, User $administrator, ?string $note): PluginPaymentTransaction
    {
        throw_if($transaction->status !== 'submitted', ValidationException::withMessages([
            'payment' => 'يمكن رفض عمليات الدفع المرسلة فقط.',
        ]));

        $transaction->update([
            'status' => 'rejected',
            'reviewed_by' => $administrator->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        return $transaction->fresh()->load(['plugin', 'method', 'user', 'reviewer']);
    }
}
