@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Ubah Program Promo"
        subtitle="Perbarui aturan diskon, masa aktif, atau sasaran produk untuk '{{ $promotion->name }}'.">
        <x-slot:actions>
            <a href="{{ route('promotions.index') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                <span>Kembali ke Daftar</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @include('promotions._form', ['promotion' => $promotion])
</div>
@endsection

