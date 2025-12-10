<div class="w-full max-w-sm sm:max-w-md mx-auto px-4">

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
