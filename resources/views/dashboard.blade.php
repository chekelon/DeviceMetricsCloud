<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-100 leading-tight">
            DeviceMetricsCloud
        </h2>
    </x-slot>

    <div class="py-12 w-full">
        <div class="w-full px-0 lg:px-0">
            <!-- <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <x-welcome />
            </div> -->
            @livewire('device-dashboard')
           
        </div>
    </div>

   
</x-app-layout>
