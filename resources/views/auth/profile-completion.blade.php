<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>完善您的個人資料 - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* 簡單的內聯樣式，確保即使 Tailwind 未編譯也能閱讀 */
        body {
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>

<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            @if($user->avatar_url)
                <img class="mx-auto h-20 w-20 rounded-full" src="{{ $user->avatar_url }}" alt="{{ $user->name }}">
            @else
                <div
                    class="mx-auto h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-2xl text-gray-500">
                    {{ substr($user->name, 0, 1) }}
                </div>
            @endif

            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                請輸入您的真實姓名
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                為了方便校園行政作業，我們需要您提供真實姓名。
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form class="space-y-6" action="{{ route('profile.update') }}" method="POST">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">真實姓名</label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" autocomplete="name" required
                                value="{{ old('name', $user->name) }}"
                                class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button type="submit"
                            class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            確認並進入系統
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
