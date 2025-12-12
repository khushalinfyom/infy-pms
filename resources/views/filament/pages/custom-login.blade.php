<div class="w-full max-w-sm sm:max-w-md mx-auto px-4 relative"
    @php
$appSetting = \App\Models\Setting::where('key', 'app_name')->first();
        $bgImageUrl = $appSetting && $appSetting->getFirstMediaUrl('login_bg_image') ? $appSetting->getFirstMediaUrl('login_bg_image') : ''; @endphp
    @if ($bgImageUrl) style="background-image: url('{{ $bgImageUrl }}'); background-size: cover; background-position: center; background-repeat: no-repeat;" @endif>

    <div class="flex justify-center mb-6">
        <img src="{{ !empty(getLogoUrl()) ? getLogoUrl() : asset('assets/img/logo-red-black.png') }}"
            alt="{{ config('app.name') }}" class="h-14 sm:h-16 md:h-20 w-auto">
    </div>

    <div class="mt-4">
        {{ $this->content }}
    </div>

    <div class="mt-6 text-center text-xs sm:text-sm text-gray-500 dark:text-gray-300">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>

</div>
