@extends('layouts.sandbox')

@section('title', 'Bóveda Sandbox // Conciliación Activa de Bóveda')
@section('body_class', 'tech-grid')

@section('content')
    <!-- CONTENEDOR PRINCIPAL -->
    <div x-data="reconcileSandbox({ initialBags: {{ isset($initialBags) ? $initialBags->toJson() : '[]' }} })"
         x-init="init()"
         class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-6"
         x-cloak>

        <!-- 1. ESTADÍSTICAS Y PANEL DE RESUMEN (Live KPIs) -->
        <header class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-900/30 border border-slate-800/60 rounded-xl p-4 flex flex-col justify-between backdrop-blur-sm">
                <span class="text-[10px] font-mono tracking-wider text-slate-500 uppercase">Tulas Totales</span>
                <span class="text-2xl font-bold font-mono text-slate-100 mt-2" x-text="bags.length">0</span>
            </div>
            <div class="bg-slate-900/30 border border-slate-800/60 rounded-xl p-4 flex flex-col justify-between backdrop-blur-sm">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-[10px] font-mono tracking-wider text-slate-500 uppercase">Pendientes</span>
                </div>
                <span class="text-2xl font-bold font-mono text-amber-500 mt-2" x-text="bags.filter(b => b.reconciliation_status === 'pending').length">0</span>
            </div>
            <div class="bg-slate-900/30 border border-slate-800/60 rounded-xl p-4 flex flex-col justify-between backdrop-blur-sm">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-mono tracking-wider text-slate-500 uppercase">Conciliadas</span>
                </div>
                <span class="text-2xl font-bold font-mono text-emerald-500 mt-2" x-text="bags.filter(b => b.reconciliation_status === 'matched').length">0</span>
            </div>
            <div class="bg-slate-900/30 border border-slate-800/60 rounded-xl p-4 flex flex-col justify-between backdrop-blur-sm">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-[10px] font-mono tracking-wider text-slate-500 uppercase">Discrepancias</span>
                </div>
                <span class="text-2xl font-bold font-mono text-rose-500 mt-2" x-text="bags.filter(b => b.reconciliation_status === 'discrepancy').length">0</span>
            </div>
        </header>

        <!-- 2. ÁREA DE TRABAJO DE CONCILIACIÓN (Split Grid) -->
        <main class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 items-stretch">
            
            <!-- COLUMNA IZQUIERDA: INVENTARIO DE TULAS EN BÓVEDA -->
            <section class="lg:col-span-5 flex flex-col gap-4">
                <div class="flex flex-col bg-slate-900/20 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-sm flex-1 h-full max-h-[600px] overflow-hidden">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                            <span>Inventario de Bóveda</span>
                            <span class="text-xs font-mono font-normal text-slate-500" x-text="`(${filteredBags.length} tulas)`"></span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">Haga clic en una tula para certificar el arqueo físico.</p>
                    </div>

                    <!-- Buscador Técnico -->
                    <div class="relative mb-4">
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Filtrar por ID de Tula o Candado..." 
                               class="w-full bg-slate-950 border border-slate-800 rounded-lg py-2 pl-9 pr-4 text-xs font-mono text-slate-300 placeholder-slate-500 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/30">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Lista de Tarjetas Minimalistas -->
                    <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                        <template x-for="bag in filteredBags" :key="bag.id">
                            <div @click="selectBag(bag)"
                                 :class="{
                                     'border-amber-500 bg-slate-900/60 shadow-[0_0_15px_-3px_rgba(245,158,11,0.15)] ring-1 ring-amber-500/20': selectedBag && selectedBag.id === bag.id,
                                     'border-slate-800/80 bg-slate-900/20 hover:border-slate-700/80 hover:bg-slate-900/40': !selectedBag || selectedBag.id !== bag.id
                                 }"
                                 class="relative border rounded-xl p-4 cursor-pointer transition-all duration-200 flex flex-col gap-2 overflow-hidden">
                                
                                <!-- Accent Bar on Selection -->
                                <div x-show="selectedBag && selectedBag.id === bag.id" class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>

                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-sm font-bold text-white tracking-wide" x-text="bag.bag_id"></span>
                                    
                                    <!-- Dynamic Badge for reconciliation_status -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider border"
                                          :class="getStatusConfig(bag.reconciliation_status).bg"
                                          x-text="getStatusConfig(bag.reconciliation_status).label">
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 text-xs font-mono text-slate-400 mt-1">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span x-text="`Lock: ${bag.lock_id}`"></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-slate-500">Bultos:</span> <span class="font-bold text-slate-300" x-text="bag.packages_amount"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty Search Result -->
                        <div x-show="filteredBags.length === 0" class="py-12 text-center border border-dashed border-slate-800 rounded-xl bg-slate-900/10">
                            <p class="text-xs text-slate-500 font-mono">No se encontraron tulas que coincidan con la búsqueda.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- COLUMNA DERECHA: PANEL DE INSPECCIÓN HUMANA Y ARQUEO -->
            <section class="lg:col-span-7 flex flex-col gap-4">
                
                <!-- ESTADO INICIAL (No selected bag) -->
                <div x-show="!selectedBag" class="h-full border border-dashed border-slate-800/80 rounded-2xl flex flex-col items-center justify-center p-8 text-center bg-slate-900/10 min-h-[400px] backdrop-blur-sm flex-1">
                    <div class="w-12 h-12 rounded-full border border-slate-800 flex items-center justify-center text-slate-500 mb-4 animate-pulse">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-mono tracking-widest text-slate-400 uppercase font-semibold">Panel de Inspección Humana</h3>
                    <p class="text-xs text-slate-500 mt-2 max-w-xs">
                        Seleccione una tula de la bóveda para iniciar el arqueo físico
                    </p>
                </div>

                <!-- FORMULARIO TÉCNICO DE AUDITORÍA (When selected) -->
                <div x-show="selectedBag" x-transition.opacity class="bg-slate-900/20 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-sm flex-1 flex flex-col justify-between gap-6">
                    <div>
                        <!-- Header Arqueo -->
                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-4 mb-5">
                            <div>
                                <h3 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                                    <span>Inspección de Tula</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 border border-slate-700/50 font-medium" x-text="selectedBag ? selectedBag.bag_id : ''"></span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Control de arqueo y conciliación física en bóveda.</p>
                            </div>
                            <!-- Close Selection -->
                            <button @click="selectedBag = null" class="text-slate-500 hover:text-slate-300 font-mono text-xs flex items-center gap-1 bg-slate-900/60 border border-slate-850 px-2.5 py-1 rounded-md transition duration-150">
                                <span>Cerrar</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Fila de Datos Registrados (Solo Lectura) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            
                            <!-- Tarjeta Info 1 -->
                            <div class="bg-slate-950/60 border border-slate-900 rounded-xl p-4 flex flex-col justify-between">
                                <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wide">ID Interno / Base de Datos</span>
                                <span class="font-mono text-xs text-slate-300 font-semibold mt-1 break-all select-all" x-text="selectedBag ? selectedBag.id : '--'"></span>
                            </div>

                            <!-- Tarjeta Info 2 -->
                            <div class="bg-slate-950/60 border border-slate-900 rounded-xl p-4 flex flex-col justify-between">
                                <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wide">Código de Candado (Lock ID)</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="font-mono text-sm text-amber-500 font-bold select-all" x-text="selectedBag ? selectedBag.lock_id : '--'"></span>
                                </div>
                            </div>

                            <!-- Tarjeta Info 3 (Bultos Declarados) -->
                            <div class="bg-slate-950/60 border border-slate-900 rounded-xl p-4 flex flex-col justify-between">
                                <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wide">Declarado por el Camión</span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-3xl font-mono text-slate-100 font-black" x-text="selectedBag ? selectedBag.packages_amount : '0'"></span>
                                    <span class="text-[10px] font-mono text-slate-500">Bultos</span>
                                </div>
                            </div>

                            <!-- Tarjeta Info 4 (Estado Conciliación) -->
                            <div class="bg-slate-950/60 border border-slate-900 rounded-xl p-4 flex flex-col justify-between">
                                <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wide">Estado de Conciliación</span>
                                <div class="mt-2.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs uppercase font-bold tracking-wider border"
                                          :class="selectedBag ? getStatusConfig(selectedBag.reconciliation_status).bg : ''"
                                          x-text="selectedBag ? getStatusConfig(selectedBag.reconciliation_status).label : 'PENDIENTE'">
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Formulario de Arqueo Físico -->
                        <div class="bg-slate-950/40 border border-slate-900 rounded-2xl p-6 flex flex-col items-center justify-center gap-4">
                            <div class="text-center">
                                <label for="counted_packages_amount" class="block text-xs font-mono font-bold text-slate-400 tracking-wider uppercase mb-1">
                                    Arqueo de Bultos Contados
                                </label>
                                <p class="text-[11px] text-slate-500">Ingrese el conteo físico verificado de bultos en la bóveda</p>
                            </div>

                            <!-- INPUT NUMÉRICO CENTRADO Y AJUSTADO -->
                            <div class="flex flex-col items-center gap-2 w-full max-w-[200px]">
                                <input type="number" 
                                       id="counted_packages_amount" 
                                       name="counted_packages_amount"
                                       x-model.number="countedAmount"
                                       min="0"
                                       placeholder="0"
                                       class="bg-slate-900 border-slate-800 text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-3xl font-mono text-center font-bold tracking-tight rounded-xl p-4 w-full border"
                                       :disabled="submitting">
                            </div>

                            <!-- DYNAMIC VISUAL HINT (Coincidencia / Discrepancia Proyectada) -->
                            <div class="h-6 flex items-center justify-center">
                                <template x-if="countedAmount !== '' && countedAmount !== null && countedAmount >= 0">
                                    <div class="flex items-center gap-1.5 text-xs font-mono tracking-wide">
                                        <template x-if="countedAmount === selectedBag.packages_amount">
                                            <span class="text-emerald-400 flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                ✔ Coincidencia limpia (Match proyectado)
                                            </span>
                                        </template>
                                        <template x-if="countedAmount !== selectedBag.packages_amount">
                                            <span class="text-rose-400 flex items-center gap-1 animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                ⚠ Alerta: Discrepancia proyectada
                                            </span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Certificación Único -->
                    <div>
                        <button @click="certifyReconciliation()" 
                                :disabled="submitting || countedAmount === '' || countedAmount === null || countedAmount < 0"
                                :class="{
                                    'opacity-50 cursor-not-allowed': submitting || countedAmount === '' || countedAmount === null || countedAmount < 0,
                                    'bg-amber-500 hover:bg-amber-400 hover:shadow-amber-500/10 text-slate-950 active:scale-[0.99]': !submitting && countedAmount !== '' && countedAmount !== null && countedAmount >= 0
                                }"
                                class="w-full py-4 px-4 font-mono font-bold rounded-xl shadow-lg border border-transparent transition-all flex items-center justify-center gap-2.5 uppercase text-xs tracking-widest shrink-0">
                            
                            <!-- Spinner de Carga -->
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            
                            <span x-text="submitting ? 'PROCESANDO ARQUEO...' : 'Certificar Arqueo en Bóveda'"></span>
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- 3. LOG DE AUDITORÍA INFERIOR -->
        <footer class="mt-auto space-y-2">
            <div class="flex items-center justify-between border-t border-slate-900 pt-4">
                <h3 class="text-xs font-mono font-semibold tracking-wider text-slate-500 uppercase flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                    <span>Consola de Auditoría de Conciliación</span>
                </h3>
                <button @click="clearLogs()" class="text-[10px] font-mono text-slate-600 hover:text-slate-400 transition-colors uppercase">
                    Limpiar Consola
                </button>
            </div>

            <div id="audit-log-console"
                 class="bg-slate-950/80 border border-slate-900 p-4 rounded-xl font-mono text-xs text-slate-500 max-h-36 overflow-y-auto space-y-1.5 shadow-inner custom-scrollbar">
                <template x-for="(log, index) in logs" :key="index">
                    <div class="flex gap-2 items-start leading-relaxed">
                        <span class="text-slate-600 shrink-0" x-text="formatLogTime(log.timestamp)"></span>
                        <span class="text-slate-400"
                              :class="{
                                  'text-amber-500/80 font-semibold': log.type === 'warn',
                                  'text-slate-500': log.type === 'info',
                                  'text-emerald-400 font-medium': log.type === 'success',
                                  'text-rose-500 font-semibold': log.type === 'error'
                              }"
                              x-text="log.message"></span>
                    </div>
                </template>
                <div x-show="logs.length === 0" class="text-slate-700 italic select-none py-1">
                    [Ninguna acción de red auditada en esta sesión]
                </div>
            </div>
        </footer>
    </div>

    <!-- Componente y Lógica Alpine.js -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reconcileSandbox', (config) => ({
                bags: config.initialBags || [],
                selectedBag: null,
                countedAmount: '',
                submitting: false,
                searchQuery: '',
                logs: [],

                init() {
                    this.log('Panel de Conciliación Activa (US-03) inicializado.');
                    this.log(`Cargadas ${this.bags.length} tulas iniciales de la bóveda.`);
                },

                get filteredBags() {
                    if (!this.searchQuery) return this.bags;
                    const query = this.searchQuery.trim().toLowerCase();
                    return this.bags.filter(bag => 
                        (bag.bag_id && bag.bag_id.toLowerCase().includes(query)) ||
                        (bag.lock_id && bag.lock_id.toLowerCase().includes(query)) ||
                        (bag.reconciliation_status && bag.reconciliation_status.toLowerCase().includes(query))
                    );
                },

                selectBag(bag) {
                    this.selectedBag = bag;
                    this.countedAmount = '';
                    this.log(`Tula seleccionada para arqueo: ${bag.bag_id} (Lock ID: ${bag.lock_id}, Declarado: ${bag.packages_amount} bultos).`);
                },

                async certifyReconciliation() {
                    if (!this.selectedBag) return;
                    
                    if (this.countedAmount === '' || this.countedAmount === null || this.countedAmount < 0) {
                        this.log('Validación fallida: El conteo físico de bultos debe ser un número entero mayor o igual a 0.', 'warn');
                        return;
                    }

                    this.submitting = true;
                    const bagId = this.selectedBag.bag_id;
                    const collectedBagId = this.selectedBag.id;
                    const amount = parseInt(this.countedAmount, 10);
                    const url = `/api/v1/collected-bags/${collectedBagId}/reconcile`;

                    this.log(`Iniciando transmisión asíncrona hacia endpoint: POST ${url} | Payload: { counted_packages_amount: ${amount} }`, 'info');

                    try {
                        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                counted_packages_amount: amount
                            })
                        });

                        const responseData = await response.json();

                        if (response.ok) {
                            const newStatus = responseData.data?.reconciliation_status || 'matched';
                            
                            const localBag = this.bags.find(b => b.id === collectedBagId);
                            if (localBag) {
                                localBag.reconciliation_status = newStatus;
                            }
                            
                            this.selectedBag.reconciliation_status = newStatus;

                            this.log(`Arqueo certificado exitosamente para la Tula ${bagId}. Resultado: ${newStatus.toUpperCase()}. HTTP 200 OK.`, 'success');
                        } else {
                            const errorMsg = responseData.message || 'Error de validación del servidor.';
                            this.log(`Fallo del servidor al registrar arqueo de Tula ${bagId} (HTTP ${response.status}): ${errorMsg}`, 'error');
                        }
                    } catch (error) {
                        this.log(`Excepción técnica de red en llamada HTTP al conciliar Tula ${bagId}: ${error.message}`, 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                getStatusConfig(status) {
                    const s = (status || '').toLowerCase();
                    if (s === 'matched') {
                        return {
                            bg: 'bg-emerald-950/40 text-emerald-400 border-emerald-500/30',
                            label: 'MATCHED'
                        };
                    } else if (s === 'discrepancy') {
                        return {
                            bg: 'bg-rose-950/40 text-rose-400 border-rose-500/30',
                            label: 'DISCREPANCY'
                        };
                    } else {
                        return {
                            bg: 'bg-amber-950/40 text-amber-400 border-amber-500/30',
                            label: 'PENDING'
                        };
                    }
                },

                log(message, type = 'info') {
                    this.logs.push({
                        timestamp: new Date(),
                        message: message,
                        type: type
                    });

                    if (this.logs.length > 100) {
                        this.logs.shift();
                    }

                    this.$nextTick(() => {
                        const consoleDiv = document.getElementById('audit-log-console');
                        if (consoleDiv) {
                            consoleDiv.scrollTop = consoleDiv.scrollHeight;
                        }
                    });
                },

                formatLogTime(dateObj) {
                    if (!dateObj) return '';
                    const date = new Date(dateObj);
                    return `[${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}]`;
                },

                clearLogs() {
                    this.logs = [];
                    this.log('Consola de auditoría de red reiniciada.', 'info');
                }
            }));
        });
    </script>
@endsection
