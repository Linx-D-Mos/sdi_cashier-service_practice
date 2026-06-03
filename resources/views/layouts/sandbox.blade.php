<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SDI FINTECH // Sandbox')</title>

    <!-- Google Fonts: Instrument Sans + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @else
        <script style="display:none">console.warn('Vite asset bundle no detectado.');</script>
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

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Estilos de grilla y animaciones comunes -->
    <style>
        [x-cloak] { display: none !important; }

        .tech-grid {
            background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.02) 1px, transparent 0);
            background-size: 20px 20px;
        }

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

        /* Personalización de barra de desplazamiento para un look técnico */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(51, 65, 85, 0.5);
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(245, 158, 11, 0.4);
        }
    </style>
    @yield('styles')
</head>
<body class="h-full font-sans antialiased text-slate-100 @yield('body_class', 'tech-grid') flex flex-col min-h-screen">

    <!-- NAVBAR GLOBAL (Estilo Técnico Minimalista) -->
    <nav class="sticky top-0 z-50 bg-slate-900/80 backdrop-blur-md border-b border-slate-800/80 py-4 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <!-- Left: Logo -->
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></span>
                <span class="font-mono text-sm font-semibold tracking-wider text-slate-200">
                    SDI FINTECH <span class="text-slate-500">//</span> <span class="text-slate-400">Sandbox Control Plane</span>
                </span>
            </div>
            <!-- Right: Navigation Tabs -->
            <div class="flex flex-wrap gap-1.5 font-mono text-xs">
                <a href="/sandbox" 
                   class="px-3.5 py-2 rounded-lg transition-all duration-150 border {{ Request::is('sandbox') ? 'text-amber-400 bg-amber-950/40 border-amber-900/60 font-bold shadow-[0_0_12px_rgba(245,158,11,0.05)]' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border-transparent' }}">
                    US-01: Terminal de Caja
                </a>
                <a href="/sandbox-vault" 
                   class="px-3.5 py-2 rounded-lg transition-all duration-150 border {{ Request::is('sandbox-vault') ? 'text-amber-400 bg-amber-950/40 border-amber-900/60 font-bold shadow-[0_0_12px_rgba(245,158,11,0.05)]' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border-transparent' }}">
                    US-02: Monitor Inmutable
                </a>
                <a href="/sandbox-reconcile" 
                   class="px-3.5 py-2 rounded-lg transition-all duration-150 border {{ Request::is('sandbox-reconcile') ? 'text-amber-400 bg-amber-950/40 border-amber-900/60 font-bold shadow-[0_0_12px_rgba(245,158,11,0.05)]' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border-transparent' }}">
                    US-03: Conciliación Activa
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    @yield('content')

</body>
</html>
