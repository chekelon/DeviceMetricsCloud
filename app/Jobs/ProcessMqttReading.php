<?php

namespace App\Jobs;

use App\Models\Reading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMqttReading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $readingData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $readingData)
    {
        $this->readingData = $readingData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
    
            Reading::create($this->readingData);
            

        } catch (\Exception $e) {
            Log::error('Error al guardar la lectura desde la cola: ' . $e->getMessage());
        }
    }
}
