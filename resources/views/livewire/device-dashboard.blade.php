

<div class="bg-tech-dark text-gray-100">
    <!-- Scanline effect -->
     <!-- <div class="scanline"></div> -->
     
     
    
    <div x-data="dashboardApp()" x-init="init()" class="min-h-screen grid-bg">
        <!-- Header -->
        <header class="border-b border-tech-green/20 bg-tech-panel/80 backdrop-blur-sm sticky top-0 z-40">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full" :class="mqttConnected ? 'bg-tech-green online-indicator' : 'bg-tech-red'"></div>
                            <h1 class="text-2xl font-bold tracking-wider text-tech-green">DeviceMetricsCloud</h1>
                        </div>
                        <div class="text-xs text-gray-500 border-l border-tech-border pl-4">
                            <div>MQTT: <span :class="mqttConnected ? 'text-tech-green' : 'text-tech-red'" x-text="mqttConnected ? 'CONECTADO' : 'DESCONECTADO'"></span></div>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </header>

        <div class="container-full mx-auto px-6 py-8">
            <div class="grid grid-cols-12 gap-6">
                <!-- Sidebar - Ubicaciones -->
                <div class="col-span-12 lg:col-span-3">
                    <div class="bg-tech-panel border border-tech-border rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-tech-border bg-gradient-to-r from-tech-green/10 to-transparent">
                            <h2 class="font-bold text-sm tracking-wider text-tech-green">UBICACIONES</h2>
                        </div>
                        
                        <div class="p-4 space-y-2">
                            <template x-for="ubicacion in ubicaciones" :key="ubicacion.id">
                                <button 
                                    @click="selectUbicacion(ubicacion)"
                                    class="w-full text-left p-3 rounded border transition-all duration-200 hover:border-tech-green/50"
                                    :class="selectedUbicacion?.id === ubicacion.id ? 
                                        'border-tech-green bg-tech-green/10 text-tech-green' : 
                                        'border-tech-border hover:bg-tech-panel text-gray-400'" >
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium" x-text="ubicacion.nombre"></span>
                                        <div class="flex items-center space-x-2">
                                            <div class="text-xs" x-text="ubicacion.sensores.length"></div>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    
                </div>

                <!-- Main Content - Sensores -->
                <div class="col-span-12 lg:col-span-9">
                    <!-- No ubicación seleccionada -->
                    <div x-show="!selectedUbicacion" class="text-center py-20">
                        <svg class="w-20 h-20 mx-auto text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        <h3 class="text-xl text-gray-500">Selecciona una ubicación</h3>
                        <p class="text-sm text-gray-600 mt-2">Elige una ubicación del panel izquierdo para ver sus sensores</p>
                    </div>

                    <!-- Sensores Grid -->
                    <div x-show="selectedUbicacion" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold" x-text="selectedUbicacion?.nombre"></h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    <span x-text="selectedUbicacion?.sensores.length"></span> sensores activos
                                </p>
                            </div>
                            <div class="text-xs text-gray-500">
                                Última actualización: <span x-text="lastUpdate"></span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <template x-for="sensor in selectedUbicacion?.sensores" :key="sensor.id">

                                <!-- ✅ ÚNICO elemento raíz del x-for: contiene AMBAS cards -->
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">

                                    <!-- ══ CARD SENSOR ══ -->
                                    <div class="bg-tech-panel border border-tech-border rounded-lg overflow-hidden slide-in flex flex-col h-full">
                                        <div class="p-4 border-b border-tech-border bg-gradient-to-r from-tech-green/5 to-transparent">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-2 h-2 rounded-full"
                                                        :class="sensorStatus[selectedUbicacion?.location]?.online ? 'bg-tech-green' : 'bg-tech-red'"></div>
                                                    <div>
                                                        <div class="text-sm font-bold text-tech-green" x-text="'SENSOR #' + sensor.id"></div>
                                                        <div class="text-xs text-gray-500 uppercase" x-text="sensor.tipo"></div>
                                                    </div>
                                                </div>
                                                <div class="px-2 py-1 rounded text-xs font-bold"
                                                    :class="sensorStatus[selectedUbicacion?.location]?.online ? 'bg-tech-green/20 text-tech-green' : 'bg-tech-red/20 text-tech-red'"
                                                    x-text="sensorStatus[selectedUbicacion?.location]?.online ? 'ONLINE' : 'OFFLINE'"></div>
                                            </div>
                                        </div>

                                        <div class="p-6 flex-1">
                                            <template x-if="sensor.tipo === 'flujo'">
                                                <div class="space-y-4">
                                                    <div class="text-center">
                                                        <div class="text-5xl font-bold text-tech-blue mb-2">
                                                            <span x-text="(sensorData[sensor.id]?.value || 0).toFixed(1)"></span>
                                                        </div>
                                                        <div class="text-sm text-gray-500">L/min</div>
                                                    </div>
                                                    <div class="h-4 bg-tech-dark rounded-full overflow-hidden">
                                                        <div class="h-full bg-gradient-to-r from-tech-blue to-tech-green transition-all duration-500"
                                                            :style="`width: ${Math.min((sensorData[sensor.id]?.value || 0) * 10, 100)}%`"></div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                                        <div class="bg-tech-dark rounded p-3">
                                                            <div class="text-xs text-gray-500">CAUDAL</div>
                                                            <div class="text-lg font-bold text-tech-blue" x-text="(sensorData[sensor.id]?.value || 0).toFixed(2)"></div>
                                                        </div>
                                                        <div class="bg-tech-dark rounded p-3">
                                                            <div class="text-xs text-gray-500">ESTADO</div>
                                                            <div class="text-lg font-bold"
                                                                :class="(sensorData[sensor.id]?.value || 0) > 0 ? 'text-tech-green' : 'text-gray-500'"
                                                                x-text="(sensorData[sensor.id]?.value || 0) > 0 ? 'ACTIVO' : 'INACTIVO'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="sensor.tipo === 'distancia'">
                                                <div class="space-y-4">
                                                    <div class="text-center">
                                                        <div class="text-5xl font-bold text-tech-yellow mb-2">
                                                            <span x-text="Math.round(sensorData[sensor.id]?.value || 0)"></span>
                                                            <span class="text-2xl">%</span>
                                                        </div>
                                                        <div class="text-sm text-gray-500">Nivel de llenado</div>
                                                    </div>
                                                    <div class="relative h-40 bg-tech-dark rounded-lg border-2 border-tech-border overflow-hidden">
                                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-tech-blue to-tech-blue/70 transition-all duration-1000"
                                                            :style="`height: ${sensorData[sensor.id]?.value || 0}%`">
                                                            <div class="absolute inset-0 opacity-30"
                                                                style="background-image: repeating-linear-gradient(0deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);"></div>
                                                        </div>
                                                        <div class="absolute inset-0 flex items-center justify-center">
                                                            <div class="text-4xl font-bold z-10"
                                                                :class="(sensorData[sensor.id]?.value || 0) > 50 ? 'text-tech-dark' : 'text-tech-yellow'"
                                                                x-text="Math.round(sensorData[sensor.id]?.value || 0) + '%'"></div>
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                                        <div class="bg-tech-dark rounded p-3">
                                                            <div class="text-xs text-gray-500">CAPACIDAD</div>
                                                            <div class="text-lg font-bold text-tech-yellow" x-text="Math.round(sensor.capacidad || 0) + ''"></div>
                                                        </div>
                                                        <div class="bg-tech-dark rounded p-3">
                                                            <div class="text-xs text-gray-500">ALERTA</div>
                                                            <div class="text-lg font-bold transition-colors duration-300"
                                                                :class="{
                                                                    'text-tech-red': (sensorData[sensor.id]?.value <= sensor.alert_min_value) || (sensorData[sensor.id]?.value >= sensor.alert_max_value),
                                                                    'text-tech-green': (sensorData[sensor.id]?.value > sensor.alert_min_value) && (sensorData[sensor.id]?.value < sensor.alert_max_value),
                                                                    'animate-pulse': (sensorData[sensor.id]?.value <= sensor.alert_min_value) || (sensorData[sensor.id]?.value >= sensor.alert_max_value)
                                                                }"
                                                                x-text="sensorData[sensor.id]?.value === undefined ? 'SIN DATOS' : (sensorData[sensor.id]?.value >= sensor.alert_max_value ? 'ALTO' : (sensorData[sensor.id]?.value <= sensor.alert_min_value ? 'CRÍTICO: BAJO' : 'ESTADO: OK'))">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                                        <div class="bg-tech-dark rounded p-3">
                                                            <div class="text-xs text-gray-500">LITROS DISPONIBLES</div>
                                                            <div class="text-lg font-bold text-tech-yellow" x-text="Math.round(sensorData[sensor.id]?.value * sensor.capacidad / 100 || 0) + ''"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="mt-auto px-4 py-2 bg-tech-dark border-t border-tech-border text-xs text-gray-500 flex items-center justify-between">
                                            <div>Última lectura: <span x-text="sensorData[sensor.id]?.timestamp || 'N/A'"></span></div>
                                            <div class="flex items-center space-x-1">
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle"></div>
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle" style="animation-delay: 0.3s;"></div>
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle" style="animation-delay: 0.6s;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- ══ FIN CARD SENSOR ══ -->

                                    <!-- ══ CARD GRÁFICA ══ -->
                                    <div class="bg-tech-panel border border-tech-border rounded-lg overflow-hidden slide-in flex flex-col h-full"
                                        x-data="{
                                            maxPoints: 30,
                                            get accentColor() { return sensor.tipo === 'flujo' ? '#00d4ff' : '#f6c90e' },
                                            get accentColorAlpha() { return sensor.tipo === 'flujo' ? 'rgba(0,212,255,0.10)' : 'rgba(246,201,14,0.10)' },
                                            get label() { return sensor.tipo === 'flujo' ? 'L/min' : '%' },
                                            initChart() {
                                                if (!window._charts) window._charts = {};
                                                if (window._charts[sensor.id]) {
                                                    window._charts[sensor.id].destroy();
                                                    delete window._charts[sensor.id];
                                                }
                                                if (window._charts[sensor.id]) return;
                                                setTimeout(() => {
                                                    
                                                
                                                const ctx = document.getElementById('chart-' + sensor.id);
                                                if (!ctx) return;
                                                window._charts[sensor.id] = new Chart(ctx, {
                                                    type: 'line',
                                                    data: {
                                                        labels: Array(this.maxPoints).fill(''),
                                                        datasets: [{
                                                            data: Array(this.maxPoints).fill(null),
                                                            borderColor: this.accentColor,
                                                            borderWidth: 2,
                                                            backgroundColor: this.accentColorAlpha,
                                                            pointRadius: 0,
                                                            pointHoverRadius: 4,
                                                            pointHoverBackgroundColor: this.accentColor,
                                                            tension: 0.4,
                                                            fill: true,
                                                        }]
                                                    },
                                                    options: {
                                                        responsive: true,
                                                        maintainAspectRatio: false,
                                                        animation: { duration: 250 },
                                                        plugins: {
                                                            legend: { display: false },
                                                            tooltip: {
                                                                backgroundColor: '#0a0f1e',
                                                                borderColor: this.accentColor,
                                                                borderWidth: 1,
                                                                titleColor: this.accentColor,
                                                                bodyColor: '#e2e8f0',
                                                                callbacks: {
                                                                    title: items => items[0].label || '',
                                                                    label: ctx => ctx.parsed.y !== null ? ctx.parsed.y.toFixed(2) + ' ' + this.label : ''
                                                                }
                                                            }
                                                        },
                                                        scales: {
                                                            x: { display: false },
                                                            y: {
                                                                display: true,
                                                                grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                                                                ticks: {
                                                                    color: '#4a5568',
                                                                    font: { size: 10 },
                                                                    maxTicksLimit: 5,
                                                                    callback: v => v + ' ' + this.label
                                                                }
                                                            }
                                                        }
                                                    }
                                                });
                                                window._charts[sensor.id].resize();
                                                }, 100);
                                            },
                                            pushValue(val) {
                                                const chart = window._charts?.[sensor.id];
                                                if (!chart || val === undefined || val === null) return;
                                                const d = chart.data.datasets[0].data;
                                                const l = chart.data.labels;
                                                d.push(parseFloat(val));
                                                l.push(new Date().toLocaleTimeString('es-ES'));
                                                if (d.length > this.maxPoints) { d.shift(); l.shift(); }
                                                chart.update('none');
                                            },
                                            getMin() {
                                                const chart = window._charts?.[sensor.id];
                                                if (!chart) return '---';
                                                const vals = chart.data.datasets[0].data.filter(v => v !== null);
                                                return vals.length ? Math.min(...vals).toFixed(1) : '---';
                                            },
                                            getMax() {
                                                const chart = window._charts?.[sensor.id];
                                                if (!chart) return '---';
                                                const vals = chart.data.datasets[0].data.filter(v => v !== null);
                                                return vals.length ? Math.max(...vals).toFixed(1) : '---';
                                            }
                                        }"
                                        x-init="$nextTick(() => initChart())"
                                        x-effect="pushValue(sensorData[sensor.id]?.value)"
                                    >
                                        <div class="p-4 border-b border-tech-border bg-gradient-to-r from-tech-green/5 to-transparent">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-2 h-2 rounded-full animate-pulse"
                                                        :class="sensor.tipo === 'flujo' ? 'bg-tech-blue' : 'bg-tech-yellow'"></div>
                                                    <div>
                                                        <div class="text-sm font-bold"
                                                            :class="sensor.tipo === 'flujo' ? 'text-tech-blue' : 'text-tech-yellow'"
                                                            x-text="'HISTÓRICO #' + sensor.id"></div>
                                                        <div class="text-xs text-gray-500">TIEMPO REAL · ÚLTIMAS 30 LECTURAS</div>
                                                    </div>
                                                </div>
                                                <div class="px-3 py-1 rounded border text-xs font-bold font-mono"
                                                    :class="sensor.tipo === 'flujo' ? 'border-tech-blue/40 bg-tech-blue/10 text-tech-blue' : 'border-tech-yellow/40 bg-tech-yellow/10 text-tech-yellow'"
                                                    x-text="(sensorData[sensor.id]?.value !== undefined ? parseFloat(sensorData[sensor.id].value).toFixed(2) : '---') + ' ' + (sensor.tipo === 'flujo' ? 'L/min' : '%')">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-6 flex-1  flex flex-col">
                                            <div class="flex-1 w-full" style="position: relative; min-height: 180px;width: 100%;">
                                                <canvas :id="'chart-' + sensor.id" style="display: block;"></canvas>
                                            </div>
                                            <div class="grid grid-cols-3 gap-3 mt-4">
                                                <div class="bg-tech-dark rounded p-3 text-center">
                                                    <div class="text-xs text-gray-500 mb-1">ACTUAL</div>
                                                    <div class="text-sm font-bold font-mono"
                                                        :class="sensor.tipo === 'flujo' ? 'text-tech-blue' : 'text-tech-yellow'"
                                                        x-text="sensorData[sensor.id]?.value !== undefined ? parseFloat(sensorData[sensor.id].value).toFixed(1) : '---'">
                                                    </div>
                                                </div>
                                                <div class="bg-tech-dark rounded p-3 text-center">
                                                    <div class="text-xs text-gray-500 mb-1">MÍN</div>
                                                    <div class="text-sm font-bold font-mono text-gray-400" x-text="getMin()"></div>
                                                </div>
                                                <div class="bg-tech-dark rounded p-3 text-center">
                                                    <div class="text-xs text-gray-500 mb-1">MÁX</div>
                                                    <div class="text-sm font-bold font-mono text-gray-400" x-text="getMax()"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-auto px-4 py-2 bg-tech-dark border-t border-tech-border text-xs text-gray-500 flex items-center justify-between">
                                            <div>Última lectura: <span x-text="sensorData[sensor.id]?.timestamp || 'N/A'"></span></div>
                                            <div class="flex items-center space-x-1">
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle"></div>
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle" style="animation-delay: 0.3s;"></div>
                                                <div class="w-1 h-1 rounded-full bg-tech-green data-particle" style="animation-delay: 0.6s;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- ══ FIN CARD GRÁFICA ══ -->

                                </div><!-- ══ FIN wrapper row ══ -->
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Status Toast -->
        <div x-show="showToast" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             class="fixed bottom-6 right-6 bg-tech-panel border-2 rounded-lg px-6 py-4 shadow-2xl z-50"
             :class="toastType === 'success' ? 'border-tech-green' : 'border-tech-red'"
        >
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full" :class="toastType === 'success' ? 'bg-tech-green' : 'bg-tech-red'"></div>
                <span x-text="toastMessage" class="text-sm"></span>
            </div>
        </div>
    </div>

    @push('js')
    <script>

    
        
        function dashboardApp() {
            
            return {
                // MQTT
                mqttClient: null,
                mqttConnected: false,
                mqttConfig: {
                    broker: 'ws://mqtt.devicemetricscloud.com/mqtt:9001',
                    username: 'cheke',
                    password: '123456'
                },
                
                // Ubicaciones y sensores
                
                ubicaciones: (() => {
                    const locations = @json($locations).map(location => ({
                        id: location.id,
                        nombre: location.name.toUpperCase(),
                        location: location.name,
                        sensores: (location.sensors || []).map(sensor => ({
                            id: sensor.id,
                            tipo: sensor.sensor_type?.name ?? '',
                            sensor: sensor.name ?? sensor.sensor ?? '',
                            almacenamiento: sensor.almacenamiento ?? '',
                            alert_min_value: sensor.alert_min_value,
                            alert_max_value: sensor.alert_max_value,
                            capacidad: sensor.capacidad ?? '',
                        }))
                    }));
                    
                    return locations;
                })(),  /*ubicaciones: [
                    {
                        id: 4,
                        nombre: 'Casa Principal',
                        location: 'casa',
                        sensores: [
                            { id: 1, tipo: 'distancia',sensor: 'sensor_distancia',almacenamiento: 'cisterna_riego' },
                            { id: 3, tipo: 'flujo',sensor: 'sensor_flujo',almacenamiento: 'cisterna_riego'},
                            
                        ]
                    },
                    {
                        id: 2,
                        nombre: 'Universidad',
                        location: 'universidad',
                        sensores: [
                            { id: 6, tipo: 'flujo',sensor: 'sensor_flujo',almacenamiento: 'cisterna_riego' },
                            { id: 7, tipo: 'distancia',sensor: 'sensor_distancia',almacenamiento: 'cisterna_riego' }
                        ]
                    },*/
                selectedUbicacion: null,
                
                // Data
                sensorData: {},
                sensorStatus: {},
                lastUpdate: '',
                historyBySensor: {},
                readingsSensors: @entangle('readingsSensors').live,
                
                // UI
                showToast: false,
                toastMessage: '',
                toastType: 'success',
                
                init() {
                    this.connectMQTT();
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },
                
                updateTime() {
                    this.lastUpdate = new Date().toLocaleTimeString('es-ES');
                },
                
                connectMQTT() {
                    try {
                        const options = {
                            keepalive: 60,
                            clean: true,
                            reconnectPeriod: 5000,
                        };
                        
                        if (this.mqttConfig.username) {
                            options.username = this.mqttConfig.username;
                            options.password = this.mqttConfig.password;
                        }
                        
                        this.mqttClient = mqtt.connect(this.mqttConfig.broker, options);
                        
                        this.mqttClient.on('connect', () => {
                            this.mqttConnected = true;
                            this.showToastMessage('Conectado al broker MQTT', 'success');
                            
                            // Suscribirse al topic de status para todos los sensores
                            this.mqttClient.subscribe('edificio/#');
                        });
                        
                        this.mqttClient.on('message', (topic, message) => {
                            this.handleMessage(topic, message);
                        });
                        
                        this.mqttClient.on('error', (error) => {
                            console.error('MQTT Error:', error);
                            this.showToastMessage('Error de conexión MQTT', 'error');
                        });
                        
                        this.mqttClient.on('close', () => {
                            this.mqttConnected = false;
                            this.showToastMessage('Desconectado del broker MQTT', 'error');
                        });
                        
                    } catch (error) {
                        console.error('Error al conectar MQTT:', error);
                        this.showToastMessage('Error al conectar con el broker', 'error');
                    }
                },
                
                reconnectMQTT() {
                    if (this.mqttClient) {
                        this.mqttClient.end(true);
                    }
                    this.connectMQTT();
                },
                
                async selectUbicacion(ubicacion) {
                    // Desuscribirse de topics anteriores
                    if (this.selectedUbicacion && this.mqttClient) {
                        this.selectedUbicacion.sensores.forEach(sensor => {
                            this.mqttClient.unsubscribe(`edificio/${ubicacion.location}/${sensor.sensor}`);
                        });
                    }
                    
                    this.selectedUbicacion = ubicacion;

                    
                    console.log('Ubicación seleccionada:', ubicacion);
                    await this.$wire.updateSensorData(ubicacion);
                    console.log('readings:',this.readingsSensors);
                    // 2) hidratar sensorData + historyBySensor con TODAS las lecturas
                    ubicacion.sensores.forEach(sensor => {
                        this.hydrateFromReadings(sensor.id, this.readingsSensors);
                    });
                    
                    
                    
                    // Suscribirse a los nuevos sensores
                    if (this.mqttClient && this.mqttConnected) {
                        console.log('Suscribiéndose a sensores de:', ubicacion.nombre);
                        ubicacion.sensores.forEach(sensor => {
                            this.mqttClient.subscribe(`edificio/${ubicacion.location}/${sensor.sensor}`);
                        });
                        this.showToastMessage(`Monitoreo activo: ${ubicacion.nombre}`, 'success');
                    }

                    this.$nextTick(() => {
                        setTimeout(() => {
                            Object.values(window._charts || {}).forEach(chart => chart.resize());
                        }, 150);
                    });
                },
                
                handleMessage(topic, message) {

                    const topicParts = topic.split('/');
                    const edificio = topicParts[1];
                    const almacenamiento = topicParts[2];
                    const ubicacion = topicParts[3];
                    const sensorTipo = topicParts[4];
                    
                    try {
                        const data = JSON.parse(message.toString());
                        
                        // Handle status messages
                        if (topic.startsWith(`edificio/${edificio}/esp32/status`)) {
                            console.log('Mensaje de status message:', `edificio/${edificio}/esp32/status`, data.value);
                            const sensorId = data.sensor_id;
                            this.sensorStatus = {
                                 ...this.sensorStatus,
                                 [data.edificio]: {
                                    online: data.value === 'online',
                                    edificio: data.edificio,
                                    timestamp: new Date().toLocaleTimeString('es-ES')
                                }
                            };
                        }
                        
                        // Handle data messages
                        if (topic.startsWith(`edificio/${edificio}/${almacenamiento}/sensor_flujo`) ) {
                            console.log('Mensaje data message:', `edificio/${edificio}/${almacenamiento}/sensor_flujo`, data.value);
                            const sensorId = data.sensor_id;
                            this.sensorData = {
                                ...this.sensorData,
                                [sensorId]: {
                                    id: sensorId,
                                    value: data.value,
                                    tipo: data.tipo,
                                    timestamp: new Date().toLocaleTimeString('es-ES')
                                }
                            };
                            console.log('Estado actual de sensorData:', this.sensorData);
                        }

                         if (topic.startsWith(`edificio/${edificio}/${almacenamiento}/sensor_distancia`) ) {
                            console.log('Mensaje data message:', `edificio/${edificio}/${almacenamiento}/sensor_distancia`, data.value);
                            const sensorId = data.sensor_id;
                            this.sensorData = {
                                ...this.sensorData,
                                [sensorId]: {
                                    value: data.value,
                                    tipo: data.tipo,
                                    alert_min_value: data.alert_min_value,
                                    alert_max_value: data.alert_max_value,
                                    timestamp: new Date().toLocaleTimeString('es-ES')
                                }
                            };
                            console.log('Estado actual de sensorData:', this.sensorData);
                        }
                        
                        this.updateTime();
                        
                        
                    } catch (error) {
                        console.error('Error al procesar mensaje:', error);
                    }
                },
                hydrateFromReadings(id,ubicacion) {
                    if(!ubicacion)return;
                    let nextSensorData = { ...this.sensorData };
                    let nextHistory = { ...this.historyBySensor };
                    console.log('Hidratando datos para sensor ID:', id, 'con readings:', ubicacion?.[id]?.readings);
                    ubicacion[id].readings.forEach(reading => {
                        const rs = ubicacion?.[id];
                        const readings =[...(rs?.readings ?? [])].reverse();

                        nextHistory[reading.sensor_id] = readings; // <-- historial completo

                        if (readings.length > 0) {
                            const last = readings[readings.length - 1];
                            nextSensorData[reading.sensor_id] = {
                                value: Number(last.value ?? 0),
                                tipo: rs?.tipo ?? 'NA',
                                timestamp: last.created_at
                                    ? new Date(last.created_at).toLocaleTimeString('es-ES')
                                    : new Date().toLocaleTimeString('es-ES')
                            };
                        }
                        // Historial completo → chart directamente
                        // Esperamos a que el DOM esté listo antes de escribir en el chart
                        this.$nextTick(() => {
                            const chart = window._charts?.[id];
                            if (!chart || readings.length === 0) return;

                            const maxPoints = 30;
                            const slice = readings.slice(-maxPoints); // últimas 30

                            chart.data.labels = slice.map(r =>
                                r.created_at
                                    ? new Date(r.created_at).toLocaleTimeString('es-ES')
                                    : ''
                            );
                            chart.data.datasets[0].data = slice.map(r => parseFloat(r.value));
                            chart.update('none');
                        });
    
                    });

                    this.historyBySensor = {...this.historyBySensor, ...nextHistory};
                    this.sensorData = {...this.sensorData, ...nextSensorData};
                      //console.log('Datos hidratados. sensorData:', this.sensorData, 'historyBySensor:', this.historyBySensor);
                },
                
                showToastMessage(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    
                    setTimeout(() => {
                        this.showToast = false;
                    }, 3000);
                }
            }
        }
    </script>
    @endpush
</div>

