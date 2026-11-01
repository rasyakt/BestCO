@extends('layouts.app')

@section('content')
@php
    $defaultRole = collect($roles)->first(fn ($role) => $role->value === 'piket_guru')?->value
        ?? ($roles[0]->value ?? 'piket_siswa');
@endphp

<div class="space-y-6">
    <x-page-header
        title="Tambah Pengguna"
        subtitle="Buat akun baru sesuai role yang berada dalam kewenangan Anda.">
        <x-slot:actions>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @include('users._form', ['user' => null, 'defaultRole' => $defaultRole])
</div>
@endsection
