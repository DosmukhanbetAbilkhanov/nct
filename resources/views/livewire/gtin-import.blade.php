<div class="container mx-auto px-4 max-w-4xl"
     @if($isProcessing) wire:poll.5s="loadBatchProgress" @endif>

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">GTIN Import</h1>
        <p class="mt-2 text-gray-600">Upload an Excel or CSV file containing GTINs to import products from the National Catalog</p>
        <p class="mt-1 text-sm text-gray-500">GTINs should be 13-digit numeric codes in column A</p>
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
                <h2 class="text-lg font-semibold text-gray-900">Upload File</h2>
                <a href="{{ asset('templates/gtin-import-template.csv') }}"
                   download="gtin-import-template.csv"
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Template
                </a>
            </div>

            <form wire:submit="upload">
                <div x-data="{
                    dragover: false,
                    filename: ''
                }"
                     @dragover.prevent="dragover = true"
                     @dragleave.prevent="dragover = false"
                     @drop.prevent="dragover = false; $wire.uploadMultiple('file', $event.dataTransfer.files)"
                     class="relative">

                    <div :class="dragover ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                         class="border-2 border-dashed rounded-lg p-12 text-center transition-colors">

                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <div class="mt-4">
                            <label for="file-upload" class="cursor-pointer">
                                <span class="text-blue-600 hover:text-blue-500 font-medium">Upload a file</span>
                                <span class="text-gray-600"> or drag and drop</span>
                                <input id="file-upload"
                                       type="file"
                                       wire:model="file"
                                       class="sr-only"
                                       accept=".xlsx,.xls,.csv"
                                       @change="filename = $event.target.files[0]?.name || ''">
                            </label>
                        </div>

                        <p class="mt-1 text-sm text-gray-500">Excel or CSV files up to 10MB</p>

                        <p x-show="filename" x-text="filename" class="mt-3 text-sm font-medium text-gray-700"></p>

                        <div wire:loading wire:target="file" class="mt-3 text-sm text-blue-600">
                            Uploading...
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="file"
                            :disabled="!$wire.file"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    @else
        {{-- Progress Section --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-semibold text-gray-900">Import Progress</h2>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        @if($currentBatch->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($currentBatch->status === 'completed') bg-green-100 text-green-800
                        @elseif($currentBatch->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($currentBatch->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-600">{{ $currentBatch->filename }}</p>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $currentBatch->total_gtins }}</div>
                    <div class="text-sm text-gray-600">Total GTINs</div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $currentBatch->processed_count }}</div>
                    <div class="text-sm text-gray-600">Processed</div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-700">{{ $currentBatch->success_count }}</div>
                    <div class="text-sm text-green-600">Successful</div>
                </div>

                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-700">{{ $currentBatch->failed_count }}</div>
                    <div class="text-sm text-red-600">Failed</div>
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
                    <span class="text-sm font-medium text-gray-700">Progress</span>
                    <span class="text-sm font-medium text-gray-700">{{ $percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
                         style="width: {{ $percentage }}%"></div>
                </div>
            </div>

            {{-- Failed Items Table --}}
            @if ($currentBatch->failed_count > 0)
                <div x-data="{ expanded: false }" class="border border-red-200 rounded-lg overflow-hidden">
                    <button @click="expanded = !expanded"
                            type="button"
                            class="w-full bg-red-50 px-4 py-3 flex items-center justify-between hover:bg-red-100 transition-colors">
                        <span class="font-medium text-red-900">
                            Failed Items ({{ $currentBatch->failed_count }})
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
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">GTIN</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($currentBatch->items()->where('status', 'failed')->get() as $item)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-gray-900">{{ $item->gtin }}</td>
                                            <td class="px-4 py-2 text-red-600">{{ $item->error_message ?? 'Unknown error' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            @if (!$isProcessing)
                <div class="mt-6 flex justify-end">
                    <button wire:click="resetImport"
                            type="button"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Upload New File
                    </button>
                </div>
            @else
                <div class="mt-6 flex items-center justify-center text-sm text-gray-600">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing... Updates every 5 seconds
                </div>
            @endif
        </div>
    @endif
</div>
