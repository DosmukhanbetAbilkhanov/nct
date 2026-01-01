<div class="isolate bg-white min-h-screen"
     @if($isProcessing) wire:poll.5s="loadBatchProgress" wire:poll.3s="loadLogs" @endif>
    {{-- Decorative background gradient --}}
    <div aria-hidden="true" class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80 pointer-events-none">
        <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="relative left-1/2 -z-10 aspect-[1155/678] w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-[72.1875rem]"></div>
    </div>

    <div class="container mx-auto px-6 py-24 sm:py-32 max-w-4xl">
        {{-- Header --}}
        <div class="mx-auto max-w-2xl text-center mb-16 sm:mb-20">
            <h1 class="text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">{{ __('import.title') }}</h1>
            <p class="mt-2 text-lg/8 text-gray-600">{{ __('import.description') }}</p>
            <p class="mt-2 text-sm text-gray-500">{{ __('import.gtin_format') }}</p>
        </div>

        {{-- Success/Error Messages --}}
        @if (session()->has('success'))
            <div class="mb-8 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-8 rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('info'))
            <div class="mb-8 rounded-md bg-indigo-50 p-4 border border-indigo-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-indigo-800">{{ session('info') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('preview_success'))
            <div class="mb-8 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('preview_success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-8 rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <ul class="list-disc list-inside text-sm font-medium text-red-800">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (!$currentBatch)
            {{-- File Upload Section --}}
            <div class="bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                <div class="p-6 sm:p-8">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('import.upload_file') }}</h2>
                        <a href="{{ asset('templates/gtin-import-template.csv') }}"
                           download="gtin-import-template.csv"
                           class="inline-flex items-center px-4 py-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('import.download_template') }}
                        </a>
                    </div>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-indigo-400 transition-colors">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <div class="mt-4">
                            <label for="file-upload" class="cursor-pointer">
                                <span class="text-indigo-600 hover:text-indigo-500 font-semibold">{{ __('import.upload_a_file') }}</span>
                                <input id="file-upload"
                                       type="file"
                                       wire:model="file"
                                       class="sr-only"
                                       accept=".xlsx,.xls,.csv">
                            </label>
                        </div>

                        <p class="mt-2 text-sm text-gray-500">{{ __('import.file_size_limit') }}</p>

                        <div wire:loading wire:target="file" class="mt-4">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm font-medium text-indigo-600">{{ __('import.uploading_file') }}</p>
                            </div>
                        </div>

                        @if ($file)
                            <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-200 rounded-md">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm font-medium text-indigo-900">{{ $file->getClientOriginalName() }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Preview Stats --}}
                    @if ($previewGtinCount !== null && count($previewStats) > 0)
                        <div class="mt-6 bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                            <h3 class="text-base font-semibold text-indigo-900 mb-4">{{ __('import.import_preview') }}</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">{{ __('import.total_gtins') }}:</span>
                                    <span class="font-bold text-gray-900 ml-2">{{ $previewStats['total_gtins'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">{{ __('import.processing_mode') }}:</span>
                                    <span class="font-semibold text-indigo-700 ml-2">{{ $previewStats['processing_mode'] }}</span>
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
                        <div class="mt-8 flex justify-end">
                            <button type="button"
                                    wire:click="startImport"
                                    wire:loading.attr="disabled"
                                    wire:target="startImport"
                                    class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <span wire:loading.remove wire:target="startImport">{{ __('import.start_import') }}</span>
                                <span wire:loading wire:target="startImport" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('import.processing') }}
                                </span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Progress Section --}}
            <div class="bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                <div class="p-6 sm:p-8">
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
                    <div class="mt-6 bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                        <h3 class="text-base font-semibold text-indigo-900 mb-4">{{ __('import.download_export_files') }}</h3>
                        <div class="flex flex-wrap gap-3">
                            @if ($currentBatch->success_file_path)
                                <a href="{{ $currentBatch->success_file_url }}"
                                   class="inline-flex items-center px-4 py-2.5 text-sm font-semibold bg-green-600 text-white rounded-md hover:bg-green-500 transition-colors shadow-sm cursor-pointer">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ __('import.download_successful_products', ['count' => $currentBatch->success_count]) }}
                                </a>
                            @endif

                            @if ($currentBatch->failed_file_path)
                                <a href="{{ $currentBatch->failed_file_url }}"
                                   class="inline-flex items-center px-4 py-2.5 text-sm font-semibold bg-red-600 text-white rounded-md hover:bg-red-500 transition-colors shadow-sm cursor-pointer">
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
                    <div class="mt-8 flex justify-end">
                        <button wire:click="resetImport"
                                type="button"
                                class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-500 transition-colors shadow-sm cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            {{ __('import.upload_new_file') }}
                        </button>
                    </div>
                @else
                    <div class="mt-8 flex items-center justify-center text-sm font-medium text-gray-600">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('import.updates_every_5s') }}
                    </div>
                @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Authentication Required Modal --}}
    @if ($showAuthModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"
                 wire:click="closeAuthModal"
                 aria-hidden="true"></div>

            {{-- Modal Container --}}
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Center modal --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal panel with gradient decoration --}}
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-10">
                    {{-- Decorative gradient --}}
                    {{-- <div aria-hidden="true" class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
                        <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="relative left-1/2 -z-10 aspect-[1155/678] w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-20rem)] sm:w-[72.1875rem]"></div>
                    </div> --}}

                    {{-- Close button --}}
                    <button wire:click="closeAuthModal"
                            type="button"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 cursor-pointer transition-colors z-10">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    {{-- Modal Header --}}
                    <div class="px-6 pt-8 pb-4 text-center">
                        <h3 class="text-2xl font-semibold text-gray-900">{{ __('import.authentication_required') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('import.auth_description') }}</p>
                    </div>

                    {{-- Tabs --}}
                    <div class="border-b border-gray-200 px-6">
                        <nav class="flex -mb-px gap-8">
                            <button wire:click="switchAuthTab('login')"
                                    type="button"
                                    class="py-4 border-b-2 font-semibold text-sm cursor-pointer transition-colors {{ $activeAuthTab === 'login' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('import.login') }}
                            </button>
                            <button wire:click="switchAuthTab('register')"
                                    type="button"
                                    class="py-4 border-b-2 font-semibold text-sm cursor-pointer transition-colors {{ $activeAuthTab === 'register' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('import.register') }}
                            </button>
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="px-6 py-6">
                        {{-- Login Form --}}
                        @if ($activeAuthTab === 'login')
                            <form wire:submit.prevent="login">
                                <div class="grid grid-cols-1 gap-y-6">
                                    <div>
                                        <label for="loginEmail" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.email_or_phone') }}</label>
                                        <div class="mt-2.5">
                                            <input type="text"
                                                   id="loginEmail"
                                                   wire:model="loginEmail"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                        @error('loginEmail') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="loginPassword" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.password') }}</label>
                                        <div class="mt-2.5">
                                            <input type="password"
                                                   id="loginPassword"
                                                   wire:model="loginPassword"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                        @error('loginPassword') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex gap-x-4">
                                        <div class="flex h-6 items-center">
                                            <input type="checkbox"
                                                   id="remember"
                                                   wire:model="remember"
                                                   class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        </div>
                                        <label for="remember" class="text-sm/6 text-gray-600">
                                            {{ __('import.remember_me') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <button type="submit"
                                            class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="login">{{ __('import.login') }}</span>
                                        <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('import.logging_in') }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        @endif

                        {{-- Register Form --}}
                        @if ($activeAuthTab === 'register')
                            <form wire:submit.prevent="register">
                                <div class="grid grid-cols-1 gap-y-6">
                                    <div>
                                        <label for="registerName" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.name') }}</label>
                                        <div class="mt-2.5">
                                            <input type="text"
                                                   id="registerName"
                                                   wire:model="registerName"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                        @error('registerName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="registerEmail" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.email') }}</label>
                                        <div class="mt-2.5">
                                            <input type="email"
                                                   id="registerEmail"
                                                   wire:model="registerEmail"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                        @error('registerEmail') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <x-sms-verification
                                            phone-model="registerPhone"
                                            code-model="verificationCode"
                                            :code-sent="$codeSent"
                                            :code-verified="$codeVerified"
                                            :remaining-seconds="$this->getRemainingSeconds()"
                                        />
                                    </div>

                                    <div>
                                        <label for="registerPassword" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.password') }}</label>
                                        <div class="mt-2.5">
                                            <input type="password"
                                                   id="registerPassword"
                                                   wire:model="registerPassword"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                        @error('registerPassword') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="registerPasswordConfirmation" class="block text-sm/6 font-semibold text-gray-900">{{ __('import.confirm_password') }}</label>
                                        <div class="mt-2.5">
                                            <input type="password"
                                                   id="registerPasswordConfirmation"
                                                   wire:model="registerPasswordConfirmation"
                                                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <button type="submit"
                                            class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="register">{{ __('import.register') }}</span>
                                        <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('import.registering') }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        @endif

                        <p class="mt-6 text-xs text-center text-gray-500">
                            {{ __('import.file_preserved') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
