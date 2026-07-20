@extends('layouts.site')

@section('title', 'Kay Paolo and Zion API | Kay Paolo Shipping')

@section('banner')
<div class="page-banner"><div class="wrap"><h1>Kay Paolo API</h1><div class="breadcrumb"><a href="{{ route('blog') }}">Blog</a><span class="sep">/</span><span>API</span></div></div></div>
@endsection

@section('content')
<section>
    <div class="wrap readable">
        <div class="eyebrow">Implementation</div>
        <h2>Kay Paolo is a Blade UI on top of Zion dev API</h2>
        <p>Kay Paolo authenticates users against dev Zion Shipping, keeps the returned token in Laravel session, and forwards quote, shipment, customer, consignee, and tracking requests to the Kay Paolo API namespace in Zion.</p>
        <p>The Zion changes are additive. Existing Bocicot endpoints remain unchanged, and Kay Paolo has its own `/api/kay-paolo/*` routes.</p>
    </div>
</section>
@endsection
