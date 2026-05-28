<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-950 text-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Linx Safe-Deposit // Sandbox POS Terminal</title>

    <!-- Google Fonts: Instrument Sans (igual que el layout principal) + JetBrains Mono para consola -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vía Vite o CDN de Fallback para pruebas independientes) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Instrument Sans"', 'sans-serif'],
                            mono: ['"JetBrains Mono"', 'monospace'],
                        }
                    }
                }
            }
        </script>
    @endif

    <!-- Alpine.js CDN (Garantiza reactividad inmediata) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Estilos de Animación personalizados para Radar & Glows -->
    <style>
        [x-cloak] { display: none !important; }
        
        .radar-grid {
            background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.03) 1px, transparent 0);
            background-size: 24px 24px;
        }

        @keyframes radar-sweep {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .radar-sweep-line {
            animation: radar-sweep 6s linear infinite;
            transform-origin: 50% 50%;
        }

        .glow-amber {
            box-shadow: 0 0 50px -10px rgba(245, 158, 11, 0.4);
        }

        .glow-emerald {
            box-shadow: 0 0 40px -10px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-amber-500 selection:text-black radar-grid">

    <!-- Contenedor Principal bajo Alpine.js -->
    <div x-data="sandboxDashboard" 
         x-init="init()"
         class="min-h-full flex flex-col justify-between p-4 md:p-8 max-w-7xl mx-auto gap-6">

        <!-- HEADER TERMINAL -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-900 pb-4 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></span>
                    <h1 class="text-xs font-mono tracking-widest text-amber-500 uppercase">Estación de Monitoreo de Transporte Seguro</h1>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight mt-1">Terminal de Caja <span class="text-gray-400">#POS-04</span></h2>
            </div>
            
            <!-- Reloj y Configuración Rápida -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-mono text-gray-400">
                <div class="bg-gray-900/80 px-3 py-1.5 rounded-md border border-gray-800 flex items-center gap-2">
                    <span class="text-gray-600">HORA LOCAL:</span>
                    <span x-text="currentTime" class="text-white">--:--:--</span>
                </div>
                <div class="bg-gray-900/80 px-3 py-1.5 rounded-md border border-gray-800 flex items-center gap-2">
                    <span class="text-gray-600">CANAL:</span>
                    <span class="text-white" x-text="`store.${storeId}`">store.4</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold" 
                          :class="isPrivate ? 'bg-indigo-950 text-indigo-400 border border-indigo-900' : 'bg-emerald-950 text-emerald-400 border border-emerald-900'"
                          x-text="isPrivate ? 'Privado' : 'Público'"></span>
                </div>
            </div>
        </header>

        <!-- DASHBOARD GRID -->
        <main class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 my-4">
            
            <!-- PANEL IZQUIERDO: CONTROLES DEL SANDBOX Y SIMULADOR (4 cols) -->
            <section class="lg:col-span-4 flex flex-col gap-6">
                
                <!-- TARJETA: CONFIGURACIÓN Y ESTADO DE CONEXIÓN -->
                <div class="bg-gray-900/40 backdrop-blur-md rounded-2xl border border-gray-800 p-5 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-300 mb-4 flex items-center justify-between">
                            <span>Estado de la Conexión</span>
                            <span class="text-xs font-mono font-normal text-gray-500">Punto de Venta</span>
                        </h3>

                        <!-- Estado Visual de Laravel Echo -->
                        <div class="space-y-4">
                            <!-- Indicador de Status -->
                            <div class="flex items-center gap-4 bg-gray-950/80 p-4 rounded-xl border border-gray-900">
                                <div class="relative flex">
                                    <!-- Status Dot animations -->
                                    <template x-if="connectionStatus === 'connected' || connectionStatus === 'searching'">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    </template>
                                    <template x-if="connectionStatus === 'connecting'">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    </template>
                                    <template x-if="connectionStatus === 'simulation'">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    </template>
                                    
                                    <span class="relative inline-flex rounded-full h-3 w-3"
                                          :class="{
                                              'bg-emerald-500': connectionStatus === 'connected' || connectionStatus === 'searching',
                                              'bg-amber-500': connectionStatus === 'connecting',
                                              'bg-blue-500': connectionStatus === 'simulation',
                                              'bg-red-500': connectionStatus === 'error',
                                              'bg-gray-600': connectionStatus === 'disconnected'
                                          }"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white" x-text="getStatusLabel()"></p>
                                    <p class="text-xs text-gray-500" x-text="getStatusDesc()"></p>
                                </div>
                            </div>

                            <!-- Selector de Tienda (ID Dinámico) -->
                            <div>
                                <label for="store_id" class="block text-xs font-mono text-gray-500 mb-1.5">ID DE TIENDA (STORE_ID)</label>
                                <div class="flex gap-2">
                                    <input type="number" 
                                           id="store_id" 
                                           x-model.number="storeId" 
                                           @change="reconnectChannel()" 
                                           class="bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-white font-mono text-sm focus:outline-none focus:border-amber-500/50 flex-1"
                                           placeholder="Ej. 4">
                                </div>
                            </div>

                            <!-- Tipo de canal toggle -->
                            <div class="flex items-center justify-between bg-gray-950/40 p-3 rounded-lg border border-gray-900 text-xs">
                                <span class="text-gray-400 font-mono">Tipo de Canal:</span>
                                <div class="flex items-center gap-2">
                                    <button @click="toggleChannelType(false)" 
                                            class="px-2 py-1 rounded transition"
                                            :class="!isPrivate ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-gray-500'">
                                        Público
                                    </button>
                                    <button @click="toggleChannelType(true)" 
                                            class="px-2 py-1 rounded transition"
                                            :class="isPrivate ? 'bg-indigo-500/10 text-indigo-400 font-bold border border-indigo-500/20' : 'text-gray-500'">
                                        Privado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón para reconectar/conectar Echo si existe -->
                    <div class="mt-6">
                        <button @click="connectEcho()" 
                                class="w-full bg-gray-800 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-xs transition border border-gray-700 flex items-center justify-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.248 8H18"></path></svg>
                            Reiniciar Escucha de Canal
                        </button>
                    </div>
                </div>

                <!-- TARJETA: INYECTOR/SIMULADOR DE EVENTOS LOCALES -->
                <div class="bg-gradient-to-b from-gray-900/60 to-gray-950/20 backdrop-blur-md rounded-2xl border border-gray-800/80 p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-300">Simulador de Eventos</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-950 text-indigo-400 border border-indigo-900/30 uppercase font-mono">Prueba Local</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-4 leading-relaxed">
                            Dado que el WebSocket real requiere que levantes un servidor Reverb/Broadcaster, usa esta sección para inyectar inmediatamente el JSON exacto del backend y probar la respuesta del frontend.
                        </p>

                        <!-- Payload JSON visual editable -->
                        <div class="bg-gray-950 rounded-lg border border-gray-900 p-3.5 font-mono text-xs text-gray-300 space-y-2">
                            <div class="text-gray-600">// JSON emitido por '.truck.approaching'</div>
                            <div>{</div>
                            <div class="pl-4"><span class="text-indigo-400">"store_id"</span>: <span class="text-amber-500" x-text="storeId">4</span>,</div>
                            <div class="pl-4"><span class="text-indigo-400">"status"</span>: <span class="text-emerald-400">"IN_PROGRESS"</span>,</div>
                            <div class="pl-4"><span class="text-indigo-400">"alert_message"</span>: <span class="text-amber-400">"¡Atención! El camión blindado ha ingresado al perímetro de la tienda."</span>,</div>
                            <div class="pl-4"><span class="text-indigo-400">"timestamp"</span>: <span class="text-blue-400">"2026-05-28T14:20:00Z"</span></div>
                            <div>}</div>
                        </div>

                        <!-- Botón de Simulación de Tienda Incorrecta para demostrar filtrado -->
                        <div class="mt-3 flex items-center justify-between bg-gray-950/40 p-2.5 rounded-lg border border-gray-900/60 text-xs">
                            <span class="text-gray-500">¿Probar con ID de Tienda no coincidente?</span>
                            <button @click="simulateWrongStoreEvent()" 
                                    class="text-indigo-400 hover:text-indigo-300 font-medium transition text-[11px] underline">
                                Enviar a Tienda #<span x-text="storeId === 4 ? 7 : 4">7</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6">
                        <!-- Botón Principal de Simulación -->
                        <button @click="simulateEvent()" 
                                class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-semibold py-3 px-4 rounded-xl text-sm transition-all duration-300 shadow-[0_4px_20px_rgba(79,70,229,0.25)] hover:shadow-[0_4px_25px_rgba(79,70,229,0.35)] flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
                            <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Simular Entrada de Camión
                        </button>
                    </div>
                </div>
            </section>

            <!-- PANEL DERECHO: MONITOR DE ESTADO Y ALERTA ACTIVA (8 cols) -->
            <section class="lg:col-span-8 flex flex-col bg-gray-900/20 backdrop-blur-md rounded-2xl border border-gray-800/80 overflow-hidden min-h-[480px]">
                
                <!-- MONITOR EN TIEMPO REAL -->
                <div class="flex-1 flex flex-col items-center justify-center p-6 relative">
                    
                    <!-- ESTADO 1: MONITOREANDO SIN ALERTA (Radar Animado) -->
                    <div x-show="!hasAlert" 
                         x-transition:enter="transition ease-out duration-500 delay-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="flex flex-col items-center justify-center text-center space-y-6 select-none max-w-md w-full">
                        
                        <!-- Radar animado -->
                        <div class="relative w-48 h-48 rounded-full border border-gray-800/60 bg-gray-950/60 flex items-center justify-center overflow-hidden">
                            <!-- Líneas concéntricas -->
                            <div class="absolute w-36 h-36 rounded-full border border-gray-900/60"></div>
                            <div class="absolute w-24 h-24 rounded-full border border-gray-900/40"></div>
                            <div class="absolute w-12 h-12 rounded-full border border-gray-900/20"></div>
                            
                            <!-- Ejes Cruzados -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-full h-[1px] bg-gray-900/30"></div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-[1px] h-full bg-gray-900/30"></div>
                            </div>

                            <!-- Pings de Onda de Escaneo -->
                            <div class="absolute w-24 h-24 rounded-full bg-emerald-500/5 animate-[ping_3s_infinite_ease-in-out]"></div>
                            <div class="absolute w-36 h-36 rounded-full bg-emerald-500/2 animate-[ping_4s_infinite_ease-in-out_1.5s]"></div>

                            <!-- Línea de Barrido Radar -->
                            <div class="absolute inset-0 radar-sweep-line pointer-events-none">
                                <div class="w-1/2 h-full bg-gradient-to-r from-emerald-500/10 to-transparent" style="clip-path: polygon(0 50%, 100% 0, 100% 50%);"></div>
                            </div>

                            <!-- Punto Central (POS) -->
                            <div class="relative w-3.5 h-3.5 bg-emerald-500 rounded-full flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.8)] border-2 border-white"></div>
                        </div>

                        <!-- Textos de estado radar -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-white tracking-wide uppercase font-mono">Buscando Camión Blindado...</h4>
                            <p class="text-xs text-gray-500 leading-relaxed px-4">
                                Canal listo. El sistema disparará una alerta instantánea en esta pantalla en cuanto el GPS detecte el ingreso del blindado al perímetro de la Tienda <span class="text-gray-300 font-bold" x-text="`#${storeId}`">#4</span>.
                            </p>
                        </div>
                    </div>

                    <!-- ESTADO 2: ALERTA DETESTANTE (ACTIVA) -->
                    <div x-show="hasAlert" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                         class="w-full max-w-xl">

                        <!-- Tarjeta de Alerta Alta Prioridad (Fintech Neon Amber style) -->
                        <div class="bg-gradient-to-b from-amber-500/15 via-yellow-500/5 to-gray-950/40 border-2 border-amber-500/60 rounded-2xl p-6 md:p-8 glow-amber text-left relative overflow-hidden">
                            
                            <!-- Indicador de Prioridad Destellante Superior -->
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-500 animate-pulse"></div>

                            <!-- Encabezado de la Alerta -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <!-- Sirena animada (SVG) -->
                                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 animate-bounce">
                                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-mono tracking-widest text-amber-500 font-bold uppercase block">Alerta de Seguridad</span>
                                        <h3 class="text-lg font-bold text-white tracking-tight">INGRESO A PERÍMETRO DETECTADO</h3>
                                    </div>
                                </div>
                                <div class="bg-amber-500/20 text-amber-300 font-mono text-xs px-2.5 py-1 rounded-md border border-amber-500/30 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                    <span>EN CURSO</span>
                                </div>
                            </div>

                            <!-- Contenido del Mensaje -->
                            <div class="bg-gray-950/70 border border-gray-900 rounded-xl p-4 mb-6">
                                <p class="text-sm text-gray-400 font-mono text-[11px] uppercase tracking-wider mb-1">MENSAJE DEL SISTEMA:</p>
                                <p class="text-base md:text-lg text-white font-medium leading-snug" x-text="currentAlert?.alert_message">
                                    ¡Atención! El camión blindado ha ingresado al perímetro de la tienda.
                                </p>
                            </div>

                            <!-- Detalles de Llegada & Metadatos -->
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-900/60 p-3 rounded-lg border border-gray-800/50">
                                    <span class="block text-[10px] font-mono text-gray-500 uppercase">Hora de Detección</span>
                                    <span class="text-sm font-semibold text-white" x-text="currentAlert?.formattedTime || '--:--:--'">14:20:00</span>
                                </div>
                                <div class="bg-gray-900/60 p-3 rounded-lg border border-gray-800/50">
                                    <span class="block text-[10px] font-mono text-gray-500 uppercase">Tienda Destino</span>
                                    <span class="text-sm font-semibold text-white" x-text="`Tienda #${currentAlert?.store_id}`">Tienda #4</span>
                                </div>
                            </div>

                            <!-- Botón de Confirmación y Cierre de Alerta -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button @click="dismissAlert()" 
                                        class="flex-1 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-gray-950 font-bold py-3.5 px-6 rounded-xl text-sm transition duration-200 flex items-center justify-center gap-2 shadow-[0_4px_20px_rgba(245,158,11,0.2)] hover:shadow-[0_4px_25px_rgba(245,158,11,0.3)]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    Entendido, Preparar Tulas
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </section>
        </main>

        <!-- REGISTRO DE AUDITORÍA / LOGS INFERIOR -->
        <footer class="bg-gray-900/30 backdrop-blur-md border border-gray-800 rounded-2xl overflow-hidden flex flex-col h-[220px]">
            <!-- Barra superior del terminal log -->
            <div class="bg-gray-950/80 px-4 py-2 border-b border-gray-800 flex justify-between items-center text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center">
                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="font-mono text-gray-400 tracking-wider">REGISTRO DE EVENTOS (LIVE AUDIT FEED)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="clearLogs()" 
                            class="text-gray-500 hover:text-gray-300 font-mono text-[11px] px-2 py-0.5 rounded border border-gray-800 hover:bg-gray-900 transition">
                        Limpiar Historial
                    </button>
                </div>
            </div>
            
            <!-- Consola de Auditoría -->
            <div class="flex-1 p-4 font-mono text-xs overflow-y-auto space-y-1.5 scrollbar-thin scrollbar-thumb-gray-800" id="audit-logs">
                <template x-if="logs.length === 0">
                    <div class="text-gray-600 italic">Esperando eventos o logs del sistema...</div>
                </template>
                <template x-for="log in logs" :key="log.id">
                    <div class="flex items-start gap-2 py-0.5 border-b border-gray-900/30 text-gray-300">
                        <span class="text-gray-600 select-none font-light" x-text="`[${log.time}]`">&nbsp;</span>
                        <span class="flex-1 leading-relaxed" 
                              :class="{
                                  'text-amber-400 font-medium': log.text.includes('¡Atención!') || log.text.includes('Evento recibido'),
                                  'text-emerald-400': log.text.includes('Conectado') || log.text.includes('Escuchando'),
                                  'text-blue-400': log.text.includes('Simulando'),
                                  'text-indigo-400': log.text.includes('canal'),
                                  'text-red-400 font-semibold': log.text.includes('Error') || log.text.includes('Desconectado'),
                                  'text-gray-500': log.text.includes('descargada') || log.text.includes('limpiado')
                              }"
                              x-html="log.text"></span>
                    </div>
                </template>
            </div>
        </footer>

    </div>

    <!-- Alpine JS Application Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sandboxDashboard', () => ({
                storeId: 4,
                isPrivate: false, // Por defecto público para pruebas rápidas
                connectionStatus: 'disconnected', // 'connected', 'searching', 'connecting', 'disconnected', 'simulation', 'error'
                hasAlert: false,
                currentAlert: null,
                logs: [],
                currentTime: '--:--:--',
                channelInstance: null,

                init() {
                    // Actualizar el reloj en vivo
                    this.startClock();

                    // Registrar arranque inicial
                    this.addLog('Consola del punto de venta inicializada.');
                    this.addLog('Pulse "Simular Entrada de Camión" para inyectar un WebSocket de prueba.');

                    // Intentar conectar automáticamente con el canal parametrizado
                    this.connectEcho();
                },

                // Reloj en tiempo real
                startClock() {
                    const updateTime = () => {
                        const now = new Date();
                        this.currentTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                    };
                    updateTime();
                    setInterval(updateTime, 1000);
                },

                // Administrador de conexión Laravel Echo
                connectEcho() {
                    if (!window.Echo) {
                        this.connectionStatus = 'simulation';
                        this.addLog('Laravel Echo no disponible globalmente (window.Echo). Operando en modo <strong class="text-blue-300">Simulación Local</strong>.');
                        return;
                    }

                    try {
                        this.disconnectEcho();

                        const channelName = `store.${this.storeId}`;
                        this.connectionStatus = 'connecting';
                        this.addLog(`Suscribiéndose al canal [${channelName}]...`);

                        // Escuchar en canal público o privado según configuración
                        this.channelInstance = this.isPrivate 
                            ? window.Echo.private(channelName)
                            : window.Echo.channel(channelName);

                        // Escuchar evento
                        this.channelInstance.listen('.truck.approaching', (data) => {
                            this.addLog(`<span class="text-emerald-300">WebSocket Recibido:</span> Canal '${channelName}' - Evento '.truck.approaching'`);
                            this.handleTruckEvent(data);
                        });

                        this.connectionStatus = 'searching';
                        this.addLog(`Laravel Echo conectado exitosamente. Escuchando canal <span class="text-amber-400 font-mono">${channelName}</span>.`);
                    } catch (error) {
                        this.connectionStatus = 'error';
                        this.addLog(`<strong>Error de conexión en Laravel Echo:</strong> ${error.message}`);
                    }
                },

                disconnectEcho() {
                    if (window.Echo && this.channelInstance) {
                        const channelName = `store.${this.storeId}`;
                        window.Echo.leave(channelName);
                        this.channelInstance = null;
                        this.addLog(`Suscripción finalizada para el canal [${channelName}].`);
                    }
                    this.connectionStatus = 'disconnected';
                },

                reconnectChannel() {
                    this.addLog(`Cambio de parámetros detectado. Reiniciando conexión...`);
                    this.connectEcho();
                },

                toggleChannelType(val) {
                    if (this.isPrivate === val) return;
                    this.isPrivate = val;
                    this.addLog(`Cambiando tipo de canal a: <strong>${this.isPrivate ? 'Privado' : 'Público'}</strong>`);
                    this.reconnectChannel();
                },

                // Procesador central de los datos del camión (WebSocket y local)
                handleTruckEvent(data) {
                    // Validar si el evento corresponde a esta tienda
                    if (parseInt(data.store_id) !== parseInt(this.storeId)) {
                        this.addLog(`Ignorado: Evento de camión recibido para la Tienda #${data.store_id} (Esta caja está en Tienda #${this.storeId})`);
                        return;
                    }

                    // Formatear hora
                    let formattedTime = '';
                    try {
                        const date = data.timestamp ? new Date(data.timestamp) : new Date();
                        formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                    } catch (e) {
                        formattedTime = new Date().toLocaleTimeString();
                    }

                    this.currentAlert = {
                        store_id: data.store_id,
                        status: data.status || 'IN_PROGRESS',
                        alert_message: data.alert_message || '¡Atención! El camión blindado ha ingresado al perímetro.',
                        timestamp: data.timestamp,
                        formattedTime: formattedTime
                    };

                    this.hasAlert = true;
                    this.addLog(`🚨 <strong class="text-amber-400">ALERTA DISPARADA:</strong> El camión blindado ingresó al perímetro de la tienda.`);
                },

                // Acción de descarte
                dismissAlert() {
                    this.hasAlert = false;
                    this.addLog('Alerta desactivada por la cajera ("Tulas Listas"). Estado de alerta despejado.');
                    
                    // Esperamos a la transición de salida antes de borrar los datos
                    setTimeout(() => {
                        if (!this.hasAlert) {
                            this.currentAlert = null;
                        }
                    }, 500);
                },

                // Simulador local: Envía datos correctos a la tienda seleccionada
                simulateEvent() {
                    const payload = {
                        store_id: this.storeId,
                        status: "IN_PROGRESS",
                        alert_message: "¡Atención! El camión blindado ha ingresado al perímetro de la tienda.",
                        timestamp: new Date().toISOString()
                    };

                    this.addLog(`Simulando difusión WebSocket local para Tienda #${this.storeId}...`);
                    this.handleTruckEvent(payload);
                },

                // Simulador local: Envía datos a una tienda diferente para probar lógica de descarte
                simulateWrongStoreEvent() {
                    const wrongId = this.storeId === 4 ? 7 : 4;
                    const payload = {
                        store_id: wrongId,
                        status: "IN_PROGRESS",
                        alert_message: "¡Atención! El camión blindado ha ingresado al perímetro de la tienda.",
                        timestamp: new Date().toISOString()
                    };

                    this.addLog(`Simulando difusión WebSocket local para Tienda #${wrongId}...`);
                    this.handleTruckEvent(payload);
                },

                // Escritura de Logs en consola
                addLog(message) {
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                    
                    this.logs.unshift({
                        id: 'log-' + Math.random().toString(36).substr(2, 9),
                        time: timeStr,
                        text: message
                    });

                    // Mantener límite de 50 registros
                    if (this.logs.length > 50) {
                        this.logs.pop();
                    }
                },

                clearLogs() {
                    this.logs = [];
                    this.addLog('Registro de auditoría limpiado.');
                },

                // Labels de ayuda para estado de conexión
                getStatusLabel() {
                    switch (this.connectionStatus) {
                        case 'connected': return 'Conectado a Echo';
                        case 'searching': return 'Buscando Camión';
                        case 'connecting': return 'Conectando...';
                        case 'simulation': return 'Modo Simulación';
                        case 'error': return 'Error de Conexión';
                        default: return 'Desconectado';
                    }
                },

                getStatusDesc() {
                    switch (this.connectionStatus) {
                        case 'connected': return 'Escuchando eventos en tiempo real';
                        case 'searching': return 'Monitoreando canal en vivo';
                        case 'connecting': return 'Conectando a Pusher/Reverb';
                        case 'simulation': return 'Sin servidor Echo. Use inyector local.';
                        case 'error': return 'Revisa configuración de Reverb';
                        default: return 'No suscrito a ningún canal';
                    }
                }
            }));
        });
    </script>
</body>
</html>
