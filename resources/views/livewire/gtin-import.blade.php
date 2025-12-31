<div class="container mx-auto px-4 max-w-4xl"
     @if($isProcessing) wire:poll.5s="loadBatchProgress" wire:poll.3s="loadLogs" @endif>

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('import.title') }}</h1>
        <p class="mt-2 text-gray-600">{{ __('import.description') }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ __('import.gtin_format') }}</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
            {{ session('info') }}
        </div>
    @endif

    @if (session()->has('preview_success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('preview_success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$currentBatch)
        {{-- File Upload Section --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('import.upload_file') }}</h2>
                <a href="{{ asset('templates/gtin-import-template.csv') }}"
                   download="gtin-import-template.csv"
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('import.download_template') }}
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>

                    <div class="mt-4">
                        <label for="file-upload" class="cursor-pointer">
                            <span class="text-blue-600 hover:text-blue-500 font-medium">{{ __('import.upload_a_file') }}</span>
                            <input id="file-upload"
                                   type="file"
                                   wire:model="file"
                                   class="sr-only"
                                   accept=".xlsx,.xls,.csv">
                        </label>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">{{ __('import.file_size_limit') }}</p>

                    <div wire:loading wire:target="file" class="mt-3">
                        <p class="text-sm text-blue-600">{{ __('import.uploading_file') }}</p>
                    </div>

                    @if ($file)
                        <p class="mt-3 text-sm font-medium text-gray-700">{{ $file->getClientOriginalName() }}</p>
                    @endif
                </div>

                {{-- Preview Stats --}}
                @if ($previewGtinCount !== null && count($previewStats) > 0)
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3">{{ __('import.import_preview') }}</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">{{ __('import.total_gtins') }}:</span>
                                <span class="font-bold text-gray-900 ml-2">{{ $previewStats['total_gtins'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">{{ __('import.processing_mode') }}:</span>
                                <span class="font-medium text-blue-700 ml-2">{{ $previewStats['processing_mode'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">{{ __('import.estimated_time') }}:</span>
                                <span class="font-medium text-gray-900 ml-2">{{ $previewStats['estimated_time'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">{{ __('import.chunks') }}:</span>
                                <span class="font-medium text-gray-900 ml-2">{{ $previewStats['chunks'] }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($file && $previewGtinCount !== null)
                    <div class="mt-6 flex justify-end">
                        <button type="button"
                                wire:click="startImport"
                                wire:loading.attr="disabled"
                                wire:target="startImport"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors cursor-pointer">
                            <span wire:loading.remove wire:target="startImport">{{ __('import.start_import') }}</span>
                            <span wire:loading wire:target="startImport">{{ __('import.processing') }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Progress Section --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('import.import_progress') }}</h2>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        @if($currentBatch->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($currentBatch->status === 'completed') bg-green-100 text-green-800
                        @elseif($currentBatch->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ __('import.' . $currentBatch->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-600">{{ $currentBatch->filename }}</p>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $currentBatch->total_gtins }}</div>
                    <div class="text-sm text-gray-600">{{ __('import.total_gtins') }}</div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $currentBatch->processed_count }}</div>
                    <div class="text-sm text-gray-600">{{ __('import.processed') }}</div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-700">{{ $currentBatch->success_count }}</div>
                    <div class="text-sm text-green-600">{{ __('import.successful') }}</div>
                </div>

                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-700">{{ $currentBatch->failed_count }}</div>
                    <div class="text-sm text-red-600">{{ __('import.failed') }}</div>
                </div>
            </div>

            {{-- Progress Bar --}}
            @php
                $percentage = $currentBatch->total_gtins > 0
                    ? round(($currentBatch->processed_count / $currentBatch->total_gtins) * 100)
                    : 0;
            @endphp

            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">{{ __('import.progress') }}</span>
                    <span class="text-sm font-medium text-gray-700">{{ $percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
                         style="width: {{ $percentage }}%"></div>
                </div>
            </div>

            {{-- Real-time Processing Logs --}}
            @if ($isProcessing || count($recentLogs) > 0)
                <div x-data="{ expanded: true }" class="border border-blue-200 rounded-lg overflow-hidden mb-6">
                    <button @click="expanded = !expanded"
                            type="button"
                            class="w-full bg-blue-50 px-4 py-3 flex items-center justify-between hover:bg-blue-100 transition-colors cursor-pointer">
                        <span class="font-medium text-blue-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 {{ $isProcessing ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('import.real_time_logs', ['count' => count($recentLogs)]) }}
                        </span>
                        <svg :class="expanded ? 'rotate-180' : ''"
                             class="w-5 h-5 text-blue-600 transition-transform"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="expanded"
                         x-collapse
                         class="bg-gray-900 text-gray-100 p-4 font-mono text-xs overflow-x-auto max-h-96 overflow-y-auto">
                        @forelse ($recentLogs as $log)
                            <div class="mb-1 whitespace-pre-wrap break-all hover:bg-gray-800 px-2 py-1 rounded">{{ $log }}</div>
                        @empty
                            <div class="text-gray-400 text-center py-4">{{ __('import.no_logs_yet') }}</div>
                        @endforelse
                        @if ($isProcessing)
                            <div class="text-blue-400 text-center py-2 animate-pulse">{{ __('import.processing_updates') }}</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Imported Products Debug View --}}
            @if ($currentBatch->success_count > 0)
                <div x-data="{ expanded: true }" class="border border-green-200 rounded-lg overflow-hidden mb-6">
                    <button @click="expanded = !expanded"
                            type="button"
                            class="w-full bg-green-50 px-4 py-3 flex items-center justify-between hover:bg-green-100 transition-colors cursor-pointer">
                        <span class="font-medium text-green-900">
                            {{ __('import.imported_products_debug', ['count' => $currentBatch->success_count]) }}
                        </span>
                        <svg :class="expanded ? 'rotate-180' : ''"
                             class="w-5 h-5 text-green-600 transition-transform"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="expanded"
                         x-collapse
                         class="bg-white p-4">
                        @foreach ($currentBatch->items()->where('status', 'success')->with('product')->get() as $item)
                            @if ($item->product)
                                <div class="mb-4 border border-gray-200 rounded p-3">
                                    <div class="font-mono text-sm font-bold mb-2">{{ __('import.gtin') }}: {{ $item->product->gtin }}</div>
                                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto">{{ json_encode([
                                        'id' => $item->product->id,
                                        'gtin' => $item->product->gtin,
                                        'ntin' => $item->product->ntin,
                                        'nameKk' => $item->product->nameKk,
                                        'nameRu' => $item->product->nameRu,
                                        'nameEn' => $item->product->nameEn,
                                        'shortNameKk' => $item->product->shortNameKk,
                                        'shortNameRu' => $item->product->shortNameRu,
                                        'shortNameEn' => $item->product->shortNameEn,
                                        'createdDate' => $item->product->createdDate,
                                        'updatedDate' => $item->product->updatedDate,
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Failed Items Table --}}
            @if ($currentBatch->failed_count > 0)
                <div x-data="{ expanded: false }" class="border border-red-200 rounded-lg overflow-hidden">
                    <button @click="expanded = !expanded"
                            type="button"
                            class="w-full bg-red-50 px-4 py-3 flex items-center justify-between hover:bg-red-100 transition-colors cursor-pointer">
                        <span class="font-medium text-red-900">
                            {{ __('import.failed_items', ['count' => $currentBatch->failed_count]) }}
                        </span>
                        <svg :class="expanded ? 'rotate-180' : ''"
                             class="w-5 h-5 text-red-600 transition-transform"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="expanded"
                         x-collapse
                         class="bg-white">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">{{ __('import.gtin') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">{{ __('import.error') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($currentBatch->items()->where('status', 'failed')->get() as $item)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-gray-900">{{ $item->gtin }}</td>
                                            <td class="px-4 py-2 text-red-600">{{ $item->error_message ?? __('import.unknown_error') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Download Export Files --}}
            @if (!$isProcessing && ($currentBatch->success_file_path || $currentBatch->failed_file_path))
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3">{{ __('import.download_export_files') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        @if ($currentBatch->success_file_path)
                            <a href="{{ $currentBatch->success_file_url }}"
                               style="background-color: #16a34a; color: white;"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg hover:opacity-90 transition-opacity cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('import.download_successful_products', ['count' => $currentBatch->success_count]) }}
                            </a>
                        @endif

                        @if ($currentBatch->failed_file_path)
                            <a href="{{ $currentBatch->failed_file_url }}"
                               style="background-color: #dc2626; color: white;"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg hover:opacity-90 transition-opacity cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('import.download_failed_gtins', ['count' => $currentBatch->failed_count]) }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            @if (!$isProcessing)
                <div class="mt-6 flex justify-end">
                    <button wire:click="resetImport"
                            type="button"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                        {{ __('import.upload_new_file') }}
                    </button>
                </div>
            @else
                <div class="mt-6 flex items-center justify-center text-sm text-gray-600">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('import.updates_every_5s') }}
                </div>
            @endif
        </div>
    @endif

    {{-- Authentication Required Modal --}}
    @if ($showAuthModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 wire:click="closeAuthModal"
                 aria-hidden="true"></div>

            {{-- Modal Container --}}
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Center modal --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal panel --}}
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full z-10">
                    {{-- Close button --}}
                    <button wire:click="closeAuthModal"
                            type="button"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-500 cursor-pointer">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    {{-- Tabs --}}
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button wire:click="switchAuthTab('login')"
                                    type="button"
                                    class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm cursor-pointer {{ $activeAuthTab === 'login' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('import.login') }}
                            </button>
                            <button wire:click="switchAuthTab('register')"
                                    type="button"
                                    class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm cursor-pointer {{ $activeAuthTab === 'register' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('import.register') }}
                            </button>
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-6">
                        {{-- Login Form --}}
                        @if ($activeAuthTab === 'login')
                            <form wire:submit="login">
                                <div class="space-y-4">
                                    <div>
                                        <label for="loginEmail" class="block text-sm font-medium text-gray-700">{{ __('import.email_or_phone') }}</label>
                                        <input type="text"
                                               id="loginEmail"
                                               wire:model="loginEmail"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                        @error('loginEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="loginPassword" class="block text-sm font-medium text-gray-700">{{ __('import.password') }}</label>
                                        <input type="password"
                                               id="loginPassword"
                                               wire:model="loginPassword"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                        @error('loginPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               id="remember"
                                               wire:model="remember"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label for="remember" class="ml-2 block text-sm text-gray-900">{{ __('import.remember_me') }}</label>
                                    </div>

                                    <button type="submit"
                                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                                        {{ __('import.login') }}
                                    </button>
                                </div>
                            </form>
                        @endif

                        {{-- Register Form --}}
                        @if ($activeAuthTab === 'register')
                            <form wire:submit="register">
                                <div class="space-y-4">
                                    <div>
                                        <label for="registerName" class="block text-sm font-medium text-gray-700">{{ __('import.name') }}</label>
                                        <input type="text"
                                               id="registerName"
                                               wire:model="registerName"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                        @error('registerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="registerEmail" class="block text-sm font-medium text-gray-700">{{ __('import.email') }}</label>
                                        <input type="email"
                                               id="registerEmail"
                                               wire:model="registerEmail"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                        @error('registerEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <x-sms-verification
                                        phone-model="registerPhone"
                                        code-model="verificationCode"
                                        :code-sent="$codeSent"
                                        :code-verified="$codeVerified"
                                        :remaining-seconds="$this->getRemainingSeconds()"
                                    />

                                    <div>
                                        <label for="registerPassword" class="block text-sm font-medium text-gray-700">{{ __('import.password') }}</label>
                                        <input type="password"
                                               id="registerPassword"
                                               wire:model="registerPassword"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                        @error('registerPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="registerPasswordConfirmation" class="block text-sm font-medium text-gray-700">{{ __('import.confirm_password') }}</label>
                                        <input type="password"
                                               id="registerPasswordConfirmation"
                                               wire:model="registerPasswordConfirmation"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               required>
                                    </div>

                                    <button type="submit"
                                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                                        {{ __('import.register') }}
                                    </button>
                                </div>
                            </form>
                        @endif

                        <p class="mt-4 text-xs text-center text-gray-500">
                            {{ __('import.file_preserved') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
