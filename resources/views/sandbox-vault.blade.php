<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bóveda Sandbox // Terminal de Supervisión de Bóveda</title>

    <!-- Google Fonts: Instrument Sans (Sans-serif principal) + JetBrains Mono para logs/cifras -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @else
        <script style="display:none">console.warn('Vite asset bundle no detectado en producción/hot runtime.');</script>
    @endif
    <!-- Tailwind CSS (Vía CDN de Fallback para pruebas y entorno Sandbox) -->
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

    <!-- Alpine.js CDN (Garantiza reactividad inmediata) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Estilo de grilla técnica para el fondo (Estética Sandbox) -->
    <style>
        [x-cloak] { display: none !important; }

        .tech-grid {
            background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.02) 1px, transparent 0);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-slate-800 selection:text-slate-200 tech-grid">

    <!-- Contenedor Principal bajo Alpine.js -->
    <div x-data="vaultSupervisor({ storeId: {{ $storeId ?? 4 }} })"
         x-init="init()"
         class="min-h-full flex flex-col justify-between p-4 md:p-8 max-w-7xl mx-auto gap-8">

        <!-- 1. ENCABEZADO DE AUDITORÍA -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-800 pb-6 gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <!-- Status Indicator Dot -->
                    <span class="relative flex h-2 w-2">
                        <span x-show="connectionState === 'connecting'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span x-show="connectionState === 'tuned'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span x-show="connectionState === 'searching'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-400 opacity-75"></span>

                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="{
                                  'bg-amber-500': connectionState === 'connecting',
                                  'bg-emerald-500': connectionState === 'tuned',
                                  'bg-slate-500': connectionState === 'searching'
                              }"></span>
                    </span>

                    <!-- Status Label -->
                    <span class="text-xs font-mono tracking-widest uppercase font-semibold text-slate-400"
                          x-text="getConnectionStatusLabel()">CONECTANDO...</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-white mt-1.5">
                    Terminal de Bóveda <span class="text-slate-500">#Store-<span x-text="storeId">--</span></span>
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">Terminal de Supervisión de Bóveda • US-02 Monitor</p>
            </div>

            <!-- Datos Técnicos / Metadatos de Sesión -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-mono text-slate-400">
                <div class="bg-slate-900/60 px-3 py-1.5 rounded border border-slate-800 flex items-center gap-2">
                    <span class="text-slate-600">CANAL:</span>
                    <span class="text-slate-300 font-semibold" x-text="`vault.${storeId}`">vault.4</span>
                </div>
                <div class="bg-slate-900/60 px-3 py-1.5 rounded border border-slate-800 flex items-center gap-2">
                    <span class="text-slate-600">MONITOREO:</span>
                    <span class="text-amber-500/90 font-bold uppercase tracking-wider">Lector Inmutable</span>
                </div>
            </div>
        </header>

        <!-- 2. PANEL CENTRAL DE TULAS INMUTABLES -->
        <main class="flex-1 my-4 flex flex-col justify-start">
            <div class="bg-slate-900/20 border border-slate-800/80 rounded-lg overflow-hidden backdrop-blur-sm">
                <!-- Tabla de Tulas -->
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-500 text-xs font-mono tracking-wider uppercase bg-slate-900/40">
                            <th class="py-3.5 px-4 font-medium">ID Colección</th>
                            <th class="py-3.5 px-4 font-medium">ID Tula</th>
                            <th class="py-3.5 px-4 font-medium">ID Candado</th>
                            <th class="py-3.5 px-4 font-medium text-right">Cant. Paquetes</th>
                            <th class="py-3.5 px-4 font-medium">Estado</th>
                            <th class="py-3.5 px-4 font-medium text-right">Conciliación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <!-- Iteración Alpine.js de collections -->
                        <template x-for="item in collections" :key="item.bag_id">
                            <tr class="hover:bg-slate-900/30 transition-colors">
                                <td class="py-4 px-4 font-mono text-sm text-slate-400" x-text="item.external_collection_id">501</td>
                                <td class="py-4 px-4 font-mono text-sm text-slate-200 font-medium" x-text="item.bag_id">BAG-A1</td>
                                <td class="py-4 px-4 font-mono text-sm text-slate-400" x-text="item.lock_id">LOCK-X1</td>
                                <td class="py-4 px-4 font-mono text-sm text-slate-300 text-right" x-text="item.packages_amount">3</td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium tracking-wide bg-slate-800 text-slate-300 border border-slate-700/50 uppercase"
                                          x-text="item.status">DELIVERED</span>
                                </td>
                                <!-- 3. EL BADGE OBLIGATORIO -->
                                <td class="py-4 px-4 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider bg-amber-950/30 text-amber-500/90 border border-amber-900/40">
                                        Pendiente de Conciliación
                                    </span>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State (Se muestra si aún no ha llegado el evento) -->
                        <tr x-show="collections.length === 0" x-cloak>
                            <td colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <!-- Minimal pulsing circle -->
                                    <div class="relative flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-800 opacity-70"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-700"></span>
                                    </div>
                                    <div class="text-sm font-medium text-slate-400">Esperando registro del Check-out del camión blindado...</div>
                                    <div class="text-xs font-mono text-slate-600">Escuchando transmisión WebSocket en vivo desde el canal</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Leyenda Técnica de Seguridad -->
            <div class="mt-3 flex items-center justify-between text-[11px] font-mono text-slate-600 px-1">
                <span>ESTADO: SOLO LECTURA (DISPOSITIVO AUTORIZADO)</span>
                <span x-show="lastPayloadTime" class="transition-all duration-300">
                    ÚLTIMA RECEPCIÓN: <span x-text="formatTime(lastPayloadTime)" class="text-slate-500 font-semibold">--:--:--</span>
                </span>
            </div>
        </main>

        <!-- 4. LOG DE AUDITORÍA INFERIOR -->
        <footer class="mt-auto space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-mono font-semibold tracking-wider text-slate-500 uppercase">Log de Auditoría Técnica</h3>
                <span class="text-[10px] font-mono text-slate-600">Eventos en tiempo real</span>
            </div>

            <div id="audit-log-console"
                 class="bg-slate-950 border border-slate-900 p-4 rounded-lg font-mono text-xs text-slate-500 max-h-36 overflow-y-auto space-y-1.5 shadow-inner">
                <template x-for="(log, index) in logs" :key="index">
                    <div class="flex gap-2 items-start leading-relaxed">
                        <span class="text-slate-600 shrink-0" x-text="formatLogTime(log.timestamp)">[14:05:47]</span>
                        <span class="text-slate-400" :class="{ 'text-amber-500/80': log.level === 'warn', 'text-slate-500': log.level === 'info', 'text-emerald-500/80': log.level === 'success' }" x-text="log.message"></span>
                    </div>
                </template>
            </div>
        </footer>

    </div>

    <!-- Componente y Lógica Alpine.js -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vaultSupervisor', (config) => ({
                storeId: config.storeId || 4,
                collections: [],
                logs: [],
                connectionState: 'connecting', // 'connecting', 'searching', 'tuned'
                lastPayloadTime: null,

                init() {
                    this.log('Inicializando terminal de supervisión de bóveda.');
                    this.log(`Configuración: Tienda ID #${this.storeId}, Canal: vault.${this.storeId}`);
                    this.connectEcho();

                    // Exponer función de simulación global para pruebas en consola de desarrollador
                    window.simulateIncomingBags = (payload) => {
                        this.log('Inyección de payload simulado desde consola.', 'warn');
                        const data = payload || {
                            "store_id": this.storeId,
                            "timestamp": new Date().toISOString(),
                            "collections": [
                                {
                                    "external_collection_id": 501,
                                    "bag_id": "BAG-A1",
                                    "lock_id": "LOCK-X1",
                                    "packages_amount": 3,
                                    "status": "DELIVERED"
                                },
                                {
                                    "external_collection_id": 502,
                                    "bag_id": "BAG-A2",
                                    "lock_id": "LOCK-X2",
                                    "packages_amount": 1,
                                    "status": "DELIVERED"
                                },
                                {
                                    "external_collection_id": 503,
                                    "bag_id": "BAG-A3",
                                    "lock_id": "LOCK-X3",
                                    "packages_amount": 4,
                                    "status": "DELIVERED"
                                }
                            ]
                        };
                        this.processIncomingPayload(data);
                        return 'Payload inyectado con éxito.';
                    };

                    this.log('Ayuda técnica: Ejecuta "window.simulateIncomingBags()" en la consola para inyectar un payload de prueba.');
                },

                connectEcho() {
                    if (typeof window.Echo === 'undefined') {
                        this.connectionState = 'searching';
                        this.log('Laravel Echo no detectado. Reintentando sintonización de señal...', 'warn');

                        // Intentar re-detectar Echo en caso de carga diferida
                        let checkCount = 0;
                        const interval = setInterval(() => {
                            checkCount++;
                            if (typeof window.Echo !== 'undefined') {
                                clearInterval(interval);
                                this.setupEchoListeners();
                            } else if (checkCount >= 10) {
                                clearInterval(interval);
                                this.log('Laravel Echo no disponible. Interfaz esperando señal externa o simulación manual.', 'warn');
                            }
                        }, 1000);
                        return;
                    }

                    this.setupEchoListeners();
                },

                setupEchoListeners() {
                    try {
                        this.connectionState = 'connecting';
                        this.log(`Conectando con Laravel Echo al canal privado: vault.${this.storeId}`);

                        // Registrar Listener de Cambios de Conexión de Pusher si está presente
                        if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                                this.log(`Conexión de red: ${states.current.toUpperCase()}`);
                                if (states.current === 'connected') {
                                    this.connectionState = 'searching';
                                } else if (states.current === 'connecting') {
                                    this.connectionState = 'connecting';
                                } else {
                                    this.connectionState = 'searching';
                                }
                            });
                        }

                        // Suscribir al canal privado vault.{storeId}
                        const channel = window.Echo.private(`vault.${this.storeId}`);

                        // Escuchar evento de suscripción exitosa
                        channel.on('pusher:subscription_succeeded', () => {
                            this.connectionState = 'tuned';
                            this.log(`Canal sintonizado y verificado: vault.${this.storeId}`, 'success');
                        });

                        // Escuchar error de suscripción
                        channel.on('pusher:subscription_error', (status) => {
                            this.connectionState = 'searching';
                            this.log(`Error de autenticación o suscripción en canal vault.${this.storeId}: ${status}`, 'warn');
                        });

                        // Escuchar el evento backend específico (.bags.incoming)
                        channel.listen('.bags.incoming', (data) => {
                            this.log(`Evento de WebSocket recibido: .bags.incoming`);
                            this.processIncomingPayload(data);
                        });

                    } catch (error) {
                        this.connectionState = 'searching';
                        this.log(`Fallo al sintonizar canal Echo: ${error.message}`, 'warn');
                    }
                },

                processIncomingPayload(data) {
                    if (!data) return;

                    if (data.store_id && data.store_id !== this.storeId) {
                        this.log(`Payload ignorado: ID de tienda no coincide (Recibido: ${data.store_id}, Escuchando: ${this.storeId})`, 'info');
                        return;
                    }

                    this.collections = data.collections || [];
                    this.lastPayloadTime = data.timestamp || new Date().toISOString();
                    this.log(`Payload procesado. ${this.collections.length} tulas de efectivo cargadas en lista.`, 'success');

                    // Hacer scroll automático en la consola de logs
                    this.$nextTick(() => {
                        const consoleDiv = document.getElementById('audit-log-console');
                        if (consoleDiv) {
                            consoleDiv.scrollTop = consoleDiv.scrollHeight;
                        }
                    });
                },

                getConnectionStatusLabel() {
                    switch (this.connectionState) {
                        case 'connecting': return 'Conectando';
                        case 'searching': return 'Buscando Señal';
                        case 'tuned': return 'Canal Sintonizado';
                        default: return 'Desconectado';
                    }
                },

                log(message, level = 'info') {
                    this.logs.push({
                        timestamp: new Date(),
                        message: message,
                        level: level
                    });

                    // Mantener últimos 50 logs en memoria
                    if (this.logs.length > 50) {
                        this.logs.shift();
                    }

                    // Auto-scroll del log de auditoría
                    this.$nextTick(() => {
                        const consoleDiv = document.getElementById('audit-log-console');
                        if (consoleDiv) {
                            consoleDiv.scrollTop = consoleDiv.scrollHeight;
                        }
                    });
                },

                formatTime(isoString) {
                    if (!isoString) return '--:--:--';
                    const date = new Date(isoString);
                    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                },

                formatLogTime(dateObj) {
                    if (!dateObj) return '';
                    const date = new Date(dateObj);
                    return `[${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}]`;
                }
            }));
        });
    </script>
</body>
</html>
