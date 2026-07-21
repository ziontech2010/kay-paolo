@extends('layouts.site')

@section('title', 'Blog | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Blog Posts</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Blog</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="section-head">
            <div class="eyebrow">Latest News</div>
            <h2>Notes from the freight desk</h2>
            <p>Practical guides on ocean, air and land shipping - written for customers who want clarity before cargo moves.</p>
        </div>

        <div class="blog-grid">
            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--navy-800), var(--navy-950))">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <rect x="110" y="70" width="80" height="50" fill="var(--navy-700)" stroke="var(--gold-500)" stroke-width="2"></rect>
                        <path d="M0 140c50 15 100-15 150 0s100-15 150 0" stroke="var(--gold-500)" stroke-width="2" fill="none" opacity="0.5"></path>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Ocean Freight</span><span>Jun 18, 2026</span></div>
                    <h3>How Ocean Freight Rates Are Actually Calculated</h3>
                    <p>A breakdown of the variables that move your FCL and LCL quote, from fuel surcharges to seasonal demand.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>

            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--gold-300), var(--gold-500))">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <path d="M40 110 L260 60 L210 120 L160 100 L110 140 Z" fill="var(--navy-900)" opacity="0.85"></path>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Air Freight</span><span>Jun 10, 2026</span></div>
                    <h3>When Air Freight Is Worth The Premium</h3>
                    <p>Air is not always the default. Here is how to know when it pays for itself.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>

            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--teal-600), #1b4a40)">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <rect x="60" y="80" width="180" height="40" rx="4" fill="var(--navy-900)" stroke="var(--gold-300)" stroke-width="2"></rect>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Customs</span><span>May 29, 2026</span></div>
                    <h3>Five Documents That Delay Customs Clearance Most</h3>
                    <p>Missing paperwork still causes held containers. Here is what to double-check before departure.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>

            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--navy-700), var(--navy-900))">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <rect x="70" y="70" width="160" height="50" fill="var(--navy-700)" stroke="var(--gold-500)" stroke-width="2"></rect>
                        <circle cx="100" cy="130" r="10" fill="var(--navy-900)" stroke="var(--gold-500)" stroke-width="2"></circle>
                        <circle cx="200" cy="130" r="10" fill="var(--navy-900)" stroke="var(--gold-500)" stroke-width="2"></circle>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Land Freight</span><span>May 21, 2026</span></div>
                    <h3>Choosing Between FTL And LTL For Regional Runs</h3>
                    <p>A quick way to decide whether your shipment should ride alone or share a trailer.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>

            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--gold-500), var(--navy-900))">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <rect x="90" y="60" width="120" height="70" fill="var(--navy-900)" stroke="var(--gold-300)" stroke-width="2"></rect>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Warehousing</span><span>May 09, 2026</span></div>
                    <h3>Bonded Vs. General Warehousing</h3>
                    <p>If your cargo needs to sit before duties are paid, bonded storage changes your options.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>

            <a href="{{ route('blog.post') }}" class="blog-card">
                <div class="blog-thumb" style="background: linear-gradient(150deg, var(--navy-950), var(--teal-600))">
                    <svg viewBox="0 0 300 170" style="position: absolute; inset: 0; width: 100%; height: 100%">
                        <path d="M40 130c60 0 60-60 120-60s60 60 120 60" stroke="var(--gold-500)" stroke-width="2" fill="none"></path>
                    </svg>
                </div>
                <div class="blog-body">
                    <div class="blog-meta"><span>Company News</span><span>Apr 30, 2026</span></div>
                    <h3>Kay Paolo Opens A Sixth Regional Hub</h3>
                    <p>Our newest warehouse adds bonded storage capacity and shortens pickup windows.</p>
                    <span class="blog-readmore">Read More</span>
                </div>
            </a>
        </div>
    </div>
</section>
@endsection
