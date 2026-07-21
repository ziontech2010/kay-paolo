@extends('layouts.site')

@section('title', 'Receipt | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Receipt</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Receipt</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap" style="max-width: 1040px">
        @include('partials.shipment-document', [
            'documentTitle' => 'Receipt',
            'documentKicker' => 'Shipment Receipt',
        ])
    </div>
</section>
@endsection
