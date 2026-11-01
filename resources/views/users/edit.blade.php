@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Edit Pengguna"
        subtitle="Perbarui identitas, role, atau keamanan akun {{ $user->name }}.">
        <x-slot:actions>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @include('users._form', ['user' => $user])
</div>
@endsection
