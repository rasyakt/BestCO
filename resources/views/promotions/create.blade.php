@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Buat Program Promo Baru"
        subtitle="Rancang diskon persentase, potongan rupiah, atau kupon voucher dengan rentang waktu otomatis.">
        <x-slot:actions>
            <a href="{{ route('promotions.index') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                <span>Kembali ke Daftar</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @include('promotions._form', ['promotion' => null])
</div>
@endsection

