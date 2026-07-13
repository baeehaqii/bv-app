@php
    $brandLogo = filament()->getBrandLogo();
    $brandName = filament()->getBrandName();
@endphp

@push('styles')
    <style>
        .bv-login {
            display: flex;
            min-height: 100dvh;
            gap: 0;
            padding: 1.5rem;
            background: var(--gray-50);
        }

        .dark .bv-login {
            background: var(--gray-950);
        }

        .bv-login__aside {
            position: relative;
            display: none;
            flex: 1 1 0;
            overflow: hidden;
            border-radius: 1.5rem;
            padding: 2.5rem;
            color: #fff;
            background:
                linear-gradient(180deg, rgba(0, 0, 0, 0.25) 0%, rgba(0, 0, 0, 0.7) 100%),
                url('{{ asset('images/24_05_2022_pshychedelic_pattern_7_01.jpg') }}') center / cover no-repeat;
        }

        @media (min-width: 1024px) {
            .bv-login__aside {
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
            }
        }

        .bv-login__quote {
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
        }

        .bv-login__quote p {
            font-size: 1.5rem;
            line-height: 1.35;
            font-weight: 600;
        }

        .bv-login__author {
            margin-top: 1.25rem;
            font-size: 0.875rem;
        }

        .bv-login__author strong {
            display: block;
            font-weight: 600;
        }

        .bv-login__author span {
            color: rgba(255, 255, 255, 0.7);
        }

        .bv-login__main {
            display: flex;
            flex: 1 1 0;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .bv-login__card {
            width: 100%;
            max-width: 24rem;
        }

        .bv-login__logo {
            height: 3rem;
            width: auto;
            margin-bottom: 2rem;
        }

        .bv-login__card h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-950);
        }

        .dark .bv-login__card h1 {
            color: #fff;
        }

        .bv-login__card > p {
            margin-top: 0.5rem;
            margin-bottom: 2rem;
            color: var(--gray-500);
        }

        /* enlarge the login inputs */
        .bv-login__card .fi-input input,
        .bv-login__card .fi-input {
            font-size: 1rem;
        }

        .bv-login__card .fi-input-wrp {
            --tw-ring-offset-width: 0px;
        }

        .bv-login__card .fi-fo-field-wrp .fi-input-wrp input {
            padding-top: 0.85rem;
            padding-bottom: 0.85rem;
        }
    </style>
@endpush

<div class="bv-login">
    <aside class="bv-login__aside">
        <figure class="bv-login__quote">
            <p>{{ $brandName }} gives our team a single, clear view of every campaign, from plan to payout.</p>
            <figcaption class="bv-login__author">
                <strong>Gerry</strong>
                <span>Founder Beyond Viral</span>
            </figcaption>
        </figure>
    </aside>

    <main class="bv-login__main">
        <div class="bv-login__card">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="bv-login__logo" />
            @endif

            <h1>{{ __('filament-panels::auth/pages/login.title') }}</h1>
            <p>Welcome back! Please enter your details.</p>

            {{ $this->content }}
        </div>
    </main>
</div>
