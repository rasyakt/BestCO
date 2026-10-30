<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BESTCO Teaching Factory MPLB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-brand-700 text-white font-bold text-xl shadow-sm mb-3">
                BC
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">BESTCO</h1>
            <p class="text-xs text-slate-500 mt-1">Teaching Factory & ERP Laboratorium Manajemen Perkantoran dan Layanan Bisnis</p>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-lg border border-slate-200 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-4 border-b pb-2">Masuk ke Sistem Kasir / ERP</h2>

            @if($errors->has('login'))
                <div class="mb-4 p-3 rounded bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    {{ $errors->first('login') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-xs font-medium text-slate-700 mb-1">Username, NIP, atau NISN</label>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                        placeholder="Contoh: siswa.x1 atau guru.dewi"
                        class="w-full px-3 py-2 text-sm rounded border border-slate-300 focus:outline-none focus:ring-1 focus:ring-brand-600 focus:border-brand-600">
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                        placeholder="••••••••"
                        class="w-full px-3 py-2 text-sm rounded border border-slate-300 focus:outline-none focus:ring-1 focus:ring-brand-600 focus:border-brand-600">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 mr-2">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded transition duration-150 shadow-sm">
                    Masuk Sekarang
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
                <div class="font-semibold text-slate-700">Akun Pengujian Cepat:</div>
                <div>&bull; Siswa Operator: <code class="bg-slate-100 px-1 rounded">siswa.x1</code> / <code class="bg-slate-100 px-1 rounded">password</code></div>
                <div>&bull; Guru Supervisor: <code class="bg-slate-100 px-1 rounded">guru.dewi</code> / <code class="bg-slate-100 px-1 rounded">password</code> (PIN: 123456)</div>
                <div>&bull; Superadmin: <code class="bg-slate-100 px-1 rounded">superadmin</code> / <code class="bg-slate-100 px-1 rounded">password</code></div>
            </div>
        </div>
    </div>
</body>
</html>

