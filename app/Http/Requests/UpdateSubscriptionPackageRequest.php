<?php

namespace App\Http\Requests;

use App\Models\SubscriptionPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPackageRequest extends StoreSubscriptionPackageRequest
{
    public function rules(): array
    {
        /** @var SubscriptionPackage $package */
        $package = $this->route('subscriptionPackage');

        return array_merge(parent::rules(), [
            'code' => ['required', 'alpha_dash', 'max:60', Rule::unique('subscription_packages', 'code')->ignore($package)],
        ]);
    }
}
