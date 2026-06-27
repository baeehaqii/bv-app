@php
    $data = json_decode(file_get_contents(resource_path('data/beyond-viral-content.json')), true);
    $c = $data['company'];
    $contact = $data['contact'];
    $li = $data['linkedin'];
    $wa = $contact['whatsapp'];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $c['name'] }} — {{ $c['positioning'] }}</title>
    <meta name="description" content="{{ $c['description_id'] }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $c['name'] }} — {{ $c['positioning'] }}">
    <meta property="og:description" content="{{ $c['description_id'] }}">
    <meta property="og:image" content="/images/logo_bv.png">
    <meta property="og:type" content="website">

    {{-- Fonts: Outfit (display) + Work Sans (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- anime.js --}}
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.2/lib/anime.min.js"></script>

    <style>
        :root {
            --purple: #7C3AED;       /* admin-panel ungu */
            --purple-deep: #4C1D95;
            --purple-soft: #A78BFA;
            --neon: #DAFF00;         /* neon lime */
            --neon-soft: #86EFAC;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Work Sans', system-ui, sans-serif;
            background: #0B0614;
            color: #F4F1FB;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        h1, h2, h3, .display { font-family: 'Outfit', system-ui, sans-serif; }

        /* ---- Light mode ---- */
        body.light { background: #FBFAFF; color: #1A1330; }
        body.light .surface { background: #FFFFFF; border-color: #E9E3F7; }
        body.light .muted { color: #5B5273; }
        body.light .navbar { background: rgba(255,255,255,.8); border-color: #E9E3F7; }
        body.light .pill { background: #F3EFFD; color: var(--purple-deep); }
        body.light .grid-bg { opacity: .35; }

        .container { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
        .muted { color: #B0A7C9; }

        /* Glass / surface cards */
        .surface {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 20px;
            backdrop-filter: blur(8px);
        }

        a.btn, button.btn {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 15px;
            padding: 13px 26px; border-radius: 999px; cursor: pointer;
            text-decoration: none; border: none; transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
            min-height: 44px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--purple), var(--purple-deep));
            color: #fff; box-shadow: 0 8px 24px -6px rgba(124,58,237,.6);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px -6px rgba(124,58,237,.8); }
        .btn-neon {
            background: var(--neon); color: #06210A;
            box-shadow: 0 8px 24px -6px rgba(57,255,20,.45);
        }
        .btn-neon:hover { transform: translateY(-2px); box-shadow: 0 12px 32px -6px rgba(57,255,20,.65); }
        .btn-ghost { background: transparent; color: inherit; border: 1px solid rgba(255,255,255,.18); }
        body.light .btn-ghost { border-color: #D9D0F0; }
        .btn-ghost:hover { border-color: var(--neon); color: var(--neon); }
        :focus-visible { outline: 2px solid var(--neon); outline-offset: 3px; }

        .pill {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 13px; font-weight: 600; letter-spacing: .3px;
            padding: 6px 14px; border-radius: 999px;
            background: rgba(124,58,237,.18); color: var(--purple-soft);
        }
        .neon-text { color: var(--neon); text-shadow: 0 0 18px rgba(57,255,20,.45); }
        .grad-text {
            background: linear-gradient(110deg, #fff 20%, var(--purple-soft) 55%, var(--neon) 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        body.light .grad-text {
            background: linear-gradient(110deg, var(--purple-deep) 20%, var(--purple) 55%, #15803D 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        section { padding: 90px 0; position: relative; overflow: hidden; }
        .eyebrow { font-size: 13px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--neon); }
        h2.title { font-size: clamp(28px, 5vw, 46px); font-weight: 700; line-height: 1.1; margin: 14px 0 0; }
        .lead { font-size: clamp(16px,2vw,18px); line-height: 1.65; max-width: 620px; }

        /* Navbar */
        .navbar {
            position: fixed; top: 14px; left: 14px; right: 14px; z-index: 50;
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 18px; border-radius: 16px; max-width: 1180px; margin: 0 auto;
            background: rgba(11,6,20,.6); border: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(14px); transition: background .3s, box-shadow .3s;
        }
        .navbar.scrolled { box-shadow: 0 10px 30px -12px rgba(0,0,0,.6); }
        .nav-links { display: none; gap: 28px; }
        .nav-links a { color: inherit; text-decoration: none; font-size: 15px; font-weight: 500; opacity: .82; transition: color .2s, opacity .2s; }
        .nav-links a:hover { opacity: 1; color: var(--neon); }
        @media (min-width: 900px) { .nav-links { display: flex; } }

        /* Decorative background grid + glow */
        .grid-bg {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(124,58,237,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124,58,237,.08) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 40%, transparent 100%);
        }
        .glow { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .55; z-index: 0; pointer-events: none; }
        .glow.purple { background: var(--purple); }
        .glow.neon { background: var(--neon); opacity: .25; }

        /* Reveal: hidden only while JS is driving it. .js-anim is set by script
           on load, so without JS (or on error) content stays fully visible. */
        .js-anim .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
        .js-anim .reveal.in { opacity: 1; transform: none; }

        .grid { display: grid; gap: 22px; }
        @media (min-width: 760px) { .cols-3 { grid-template-columns: repeat(3,1fr); } .cols-2 { grid-template-columns: repeat(2,1fr); } .cols-4 { grid-template-columns: repeat(4,1fr); } }

        .card { padding: 28px; }
        .card .ico {
            width: 50px; height: 50px; border-radius: 14px; display: grid; place-items: center;
            background: linear-gradient(135deg, rgba(124,58,237,.25), rgba(57,255,20,.12));
            color: var(--neon); margin-bottom: 18px;
        }
        .card h3 { font-size: 20px; margin: 0 0 8px; font-weight: 600; }
        .card p { margin: 0; line-height: 1.6; font-size: 15px; }

        /* Stats */
        .stat-num { font-family: 'Outfit'; font-size: clamp(34px,5vw,52px); font-weight: 800; line-height: 1; }

        /* Partner marquee */
        .marquee { overflow: hidden; -webkit-mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent); mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent); }
        .marquee-track { display: flex; gap: 26px; width: max-content; animation: scroll-x 28s linear infinite; }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        @keyframes scroll-x { to { transform: translateX(-50%); } }
        .logo-chip {
            display: flex; align-items: center; justify-content: center;
            width: 130px; height: 96px; flex: 0 0 auto;
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); border-radius: 16px;
            padding: 16px; transition: border-color .2s, transform .2s;
        }
        body.light .logo-chip { background: #fff; border-color: #E9E3F7; }
        .logo-chip:hover { border-color: var(--neon); transform: translateY(-3px); }
        .logo-chip img { max-width: 100%; max-height: 64px; width: auto; height: auto; object-fit: contain; display: block; margin: auto; }

        /* Offer banner */
        .offer-box {
            background: linear-gradient(135deg, var(--purple-deep), #1a0b33);
            border: 1px solid rgba(57,255,20,.35); border-radius: 28px;
            padding: 48px 32px; text-align: center; position: relative; overflow: hidden;
        }
        .offer-box::after {
            content:''; position:absolute; inset:0;
            background: radial-gradient(circle at 80% 20%, rgba(57,255,20,.18), transparent 50%);
        }

        /* Testimonials */
        .quote { font-size: 16px; line-height: 1.7; }

        footer { padding: 70px 0 30px; border-top: 1px solid rgba(255,255,255,.08); position: relative; z-index: 1; }
        body.light footer { border-color: #E9E3F7; }
        .foot-grid { display: grid; gap: 30px; grid-template-columns: 1fr; }
        @media (min-width: 760px) { .foot-grid { grid-template-columns: 2fr 1fr 1fr; } }
        .foot-grid a { color: inherit; opacity: .8; text-decoration: none; transition: color .2s; }
        .foot-grid a:hover { color: var(--neon); opacity: 1; }

        /* Dark mode toggle switch */
        .switch { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; font-size: 14px; }
        .switch .track { width: 48px; height: 26px; border-radius: 999px; background: rgba(255,255,255,.15); position: relative; transition: background .25s; }
        body.light .switch .track { background: var(--purple); }
        .switch .knob { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: var(--neon); transition: transform .25s; box-shadow: 0 1px 4px rgba(0,0,0,.4); }
        body.light .switch .knob { transform: translateX(22px); background: #fff; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1 !important; transform: none !important; }
            .marquee-track { animation: none; }
            * { scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>
    {{-- ============ NAV ============ --}}
    <nav class="navbar" id="navbar">
        <a href="#hero" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
            <img src="/images/logo_bv.png" alt="{{ $c['name'] }}" style="height:30px;width:auto">
        </a>
        <div class="nav-links">
            <a href="#about">About</a>
            <a href="#service">Service</a>
            <a href="#pembeda">Pembeda</a>
            <a href="#partner">Partner</a>
            <a href="#offer">Offer</a>
        </div>
        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-neon" style="padding:9px 20px;font-size:14px">Ngobrol Sekarang</a>
    </nav>

    {{-- ============ HERO ============ --}}
    <section id="hero" style="padding-top:160px;padding-bottom:70px;text-align:center">
        <div class="grid-bg"></div>
        <div class="glow purple" style="width:420px;height:420px;top:-120px;left:50%;transform:translateX(-50%)"></div>
        <div class="glow neon" style="width:320px;height:320px;bottom:-80px;right:-60px"></div>
        <div class="container" style="position:relative;z-index:1">
            <span class="pill" id="hero-pill">⚡ {{ $c['positioning'] }}</span>
            <h1 class="display" id="hero-title" style="font-size:clamp(40px,8vw,82px);font-weight:800;line-height:1.02;margin:22px 0 0;letter-spacing:-1px">
                <span class="grad-text">{{ $c['tagline'] }}</span>
            </h1>
            <p class="lead muted" id="hero-sub" style="margin:24px auto 0;font-size:clamp(16px,2.4vw,20px)">
                {{ $data['hero']['body_id'] }}
            </p>
            <div id="hero-cta" style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:34px">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary">{{ $data['hero']['cta']['label'] }} →</a>
                <a href="#service" class="btn btn-ghost">Lihat Layanan</a>
            </div>

            {{-- Stat row --}}
            <div class="grid cols-3" id="hero-stats" style="margin-top:64px;max-width:720px;margin-left:auto;margin-right:auto">
                <div><div class="stat-num neon-text"><span class="count" data-to="20000">0</span>+</div><div class="muted" style="margin-top:6px;font-size:14px">Creators Network</div></div>
                <div><div class="stat-num neon-text"><span class="count" data-to="100">0</span>%</div><div class="muted" style="margin-top:6px;font-size:14px">Full-Service Eksekusi</div></div>
                <div><div class="stat-num neon-text"><span class="count" data-to="5">0</span></div><div class="muted" style="margin-top:6px;font-size:14px">Slot / Bulan</div></div>
            </div>
        </div>
    </section>

    {{-- ============ PARTNER (logos near top for social proof) ============ --}}
    <section id="partner" style="padding-top:30px;padding-bottom:60px">
        <div class="container">
            <p class="muted reveal" style="text-align:center;font-size:14px;letter-spacing:1px;text-transform:uppercase;margin-bottom:26px">{{ $data['partners']['heading_id'] }}</p>
            <div class="marquee reveal">
                <div class="marquee-track">
                    @foreach(array_merge($data['partners']['clients'], $data['partners']['clients']) as $p)
                        <div class="logo-chip"><img src="{{ $p['logo'] }}" alt="{{ $p['name'] }}" loading="lazy"></div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ABOUT US ============ --}}
    <section id="about">
        <div class="container">
            <div class="grid cols-2" style="align-items:center;gap:48px">
                <div class="reveal">
                    <span class="eyebrow">About Us</span>
                    <h2 class="title">Bukan cuma <span class="neon-text">KOL agency</span> biasa.</h2>
                    <p class="lead muted" style="margin-top:20px">{{ $c['description_en'] }}</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:24px">
                        @foreach($li['specialties'] as $s)
                            <span class="pill">{{ $s }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="grid cols-2 reveal" style="gap:18px">
                    <div class="surface" style="padding:24px"><div class="stat-num neon-text" style="font-size:24px">{{ $li['company_size'] }}</div><div class="muted" style="font-size:13px;margin-top:6px">Tim</div></div>
                    <div class="surface" style="padding:24px"><div class="stat-num neon-text" style="font-size:24px">Jakarta</div><div class="muted" style="font-size:13px;margin-top:6px">Markas</div></div>
                    <div class="surface" style="padding:24px"><div class="stat-num neon-text" style="font-size:24px">TikTok</div><div class="muted" style="font-size:13px;margin-top:6px">IG · YouTube</div></div>
                    <div class="surface" style="padding:24px"><div class="stat-num neon-text" style="font-size:24px">Live</div><div class="muted" style="font-size:13px;margin-top:6px">Streaming & Produksi</div></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ SERVICE (differentiators as services) ============ --}}
    <section id="service" style="background:rgba(124,58,237,.04)">
        <div class="container">
            <div style="text-align:center;max-width:620px;margin:0 auto" class="reveal">
                <span class="eyebrow">Service</span>
                <h2 class="title">{{ $data['differentiators']['heading_id'] }}</h2>
                <p class="lead muted" style="margin:18px auto 0">{{ $data['differentiators']['intro_id'] }}</p>
            </div>
            <div class="grid cols-3" style="margin-top:50px">
                @foreach($data['differentiators']['items'] as $i => $item)
                    <div class="surface card reveal">
                        <div class="ico">
                            @if($i===0)<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>
                            @elseif($i===1)<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                            @else<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>@endif
                        </div>
                        <h3>{{ $item['title'] }}</h3>
                        <p class="muted" style="font-weight:600;margin-bottom:8px">{{ $item['subtitle_id'] }}</p>
                        <p class="muted">{{ $item['body_id'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PAIN POINT ============ --}}
    <section id="painpoint">
        <div class="container">
            <div style="text-align:center;max-width:620px;margin:0 auto" class="reveal">
                <span class="eyebrow">Pain Point</span>
                <h2 class="title">{{ $data['pain_points']['heading_id'] }}</h2>
            </div>
            <div class="grid cols-3" style="margin-top:50px">
                @foreach($data['pain_points']['items'] as $item)
                    <div class="surface card reveal" style="border-color:rgba(255,80,80,.18)">
                        <div class="ico" style="background:linear-gradient(135deg,rgba(239,68,68,.22),rgba(124,58,237,.12));color:#FCA5A5">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        </div>
                        <h3>{{ $item['title_id'] }}</h3>
                        <p class="muted">{{ $item['body_id'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PEMBEDA (process steps) ============ --}}
    <section id="pembeda" style="background:rgba(124,58,237,.04)">
        <div class="container">
            <div style="text-align:center;max-width:640px;margin:0 auto" class="reveal">
                <span class="eyebrow">Pembeda</span>
                <h2 class="title">{{ $data['process']['heading_id'] }}</h2>
                <p class="lead muted" style="margin:18px auto 0">{{ $data['process']['intro_id'] }}</p>
            </div>
            <div class="grid cols-4" style="margin-top:50px">
                @foreach($data['process']['steps'] as $i => $step)
                    <div class="surface card reveal">
                        <div class="display neon-text" style="font-size:28px;font-weight:800;margin-bottom:10px">0{{ $i+1 }}</div>
                        <h3 style="font-size:18px">{{ $step['title_id'] }}</h3>
                        <p class="muted">{{ $step['body_id'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ TESTIMONIALS ============ --}}
    <section id="testimonials">
        <div class="container">
            <div style="text-align:center" class="reveal">
                <span class="eyebrow">Kata Mereka</span>
                <h2 class="title">Brand yang udah ngerasain.</h2>
            </div>
            <div class="grid cols-3" style="margin-top:50px">
                @foreach($data['testimonials'] as $t)
                    <div class="surface card reveal">
                        <div class="display" style="font-size:46px;line-height:0;color:var(--purple-soft);height:24px">&ldquo;</div>
                        <p class="quote">{{ $t['quote_id'] }}</p>
                        <div style="margin-top:18px;display:flex;align-items:center;gap:12px">
                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--neon))"></div>
                            <div><div style="font-weight:600;font-size:14px">{{ $t['author'] }}</div><div class="muted" style="font-size:13px">{{ $t['company'] }}</div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ OFFER ============ --}}
    <section id="offer">
        <div class="container">
            <div class="offer-box reveal">
                <div style="position:relative;z-index:1">
                    <span class="pill" style="background:var(--neon);color:#06210A">{{ $data['offer']['badge_id'] }}</span>
                    <h2 class="display" style="font-size:clamp(28px,5vw,44px);font-weight:800;margin:20px 0 0">{{ $data['offer']['headline_id'] }}</h2>
                    <p style="font-size:clamp(17px,2.5vw,22px);font-weight:600;margin:14px 0 0">{{ $data['offer']['body_id'] }}</p>
                    <p class="neon-text display" style="font-size:18px;font-weight:700;margin:18px 0 0">{{ $data['offer']['urgency_id'] }}</p>
                    <p class="muted lead" style="margin:14px auto 0">{{ $data['offer']['rationale_id'] }}</p>
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-neon" style="margin-top:26px">{{ $data['offer']['cta']['label'] }} →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section id="cta" style="text-align:center">
        <div class="grid-bg"></div>
        <div class="glow purple" style="width:360px;height:360px;top:0;left:50%;transform:translateX(-50%)"></div>
        <div class="container" style="position:relative;z-index:1">
            <h2 class="display reveal" style="font-size:clamp(30px,6vw,56px);font-weight:800;line-height:1.05">
                {{ $data['consultation_cta']['heading_id'] }}
            </h2>
            <div class="reveal" style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:28px">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary" style="font-size:17px;padding:16px 34px">{{ $data['consultation_cta']['cta']['label'] }} →</a>
                <a href="mailto:{{ $contact['email'] }}" class="btn btn-ghost" style="font-size:17px;padding:16px 34px">{{ $contact['email'] }}</a>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer>
        <div class="container">
            <div class="foot-grid">
                <div>
                    <img src="/images/logo_bv.png" alt="{{ $c['name'] }}" style="height:34px;width:auto;margin-bottom:16px">
                    <p class="muted" style="max-width:340px;line-height:1.6">{{ $c['description_id'] }}</p>
                    <p class="muted" style="margin-top:14px;font-size:14px">{{ $contact['address'] }}</p>
                </div>
                <div>
                    <div style="font-weight:600;margin-bottom:14px">Navigasi</div>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:15px">
                        <a href="#about">About Us</a>
                        <a href="#service">Service</a>
                        <a href="#pembeda">Pembeda</a>
                        <a href="#partner">Partner</a>
                        <a href="#offer">Offer</a>
                    </div>
                </div>
                <div>
                    <div style="font-weight:600;margin-bottom:14px">Kontak</div>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:15px">
                        <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                        <a href="tel:{{ $contact['phone'] }}">{{ $contact['phone'] }}</a>
                        <a href="{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>
                        <a href="{{ $li['url'] }}" target="_blank" rel="noopener">LinkedIn</a>
                    </div>
                    {{-- Dark mode toggle --}}
                    <label class="switch" style="margin-top:22px" id="theme-toggle">
                        <span class="track"><span class="knob"></span></span>
                        <span id="theme-label">Dark mode</span>
                    </label>
                </div>
            </div>
            <div class="muted" style="margin-top:46px;padding-top:24px;border-top:1px solid rgba(255,255,255,.07);font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <span>{{ $c['copyright'] }}</span>
                <span>{{ $c['legal_name'] }}</span>
            </div>
        </div>
    </footer>

    <script>
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        // Mark JS active so .reveal starts hidden (CSS keeps it visible if JS dies).
        if (!reduce) document.documentElement.classList.add('js-anim');

        // ---- Dark mode toggle (persisted) ----
        const body = document.body;
        const label = document.getElementById('theme-label');
        const applyTheme = (t) => {
            body.classList.toggle('light', t === 'light');
            label.textContent = t === 'light' ? 'Light mode' : 'Dark mode';
        };
        applyTheme(localStorage.getItem('bv-theme') || 'dark');
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const next = body.classList.contains('light') ? 'dark' : 'light';
            localStorage.setItem('bv-theme', next);
            applyTheme(next);
        });

        // ---- Navbar scroll state ----
        const nav = document.getElementById('navbar');
        addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 20), { passive: true });

        if (!reduce && window.anime) {
            // ---- Hero entrance timeline ----
            anime.timeline({ easing: 'easeOutExpo' })
                .add({ targets: '#hero-pill', opacity: [0,1], translateY: [16,0], duration: 700 })
                .add({ targets: '#hero-title', opacity: [0,1], translateY: [28,0], duration: 900 }, '-=450')
                .add({ targets: '#hero-sub', opacity: [0,1], translateY: [20,0], duration: 700 }, '-=550')
                .add({ targets: '#hero-cta', opacity: [0,1], translateY: [18,0], duration: 600 }, '-=450')
                .add({ targets: '#hero-stats > div', opacity: [0,1], translateY: [20,0], delay: anime.stagger(120), duration: 600 }, '-=300');

            // ---- Stat count-up ----
            setTimeout(() => {
                document.querySelectorAll('.count').forEach(el => {
                    const to = +el.dataset.to;
                    anime({ targets: { v: 0 }, v: to, round: 1, easing: 'easeOutCubic', duration: 1800,
                        update: a => { el.textContent = Math.round(a.animations[0].currentValue).toLocaleString('id-ID'); } });
                });
            }, 900);

            // ---- Scroll reveal via IntersectionObserver (CSS transition does the motion) ----
            // Cards inside a grid get a stagger delay for a nicer cascade.
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (!e.isIntersecting) return;
                    const el = e.target;
                    const siblings = [...el.parentElement.children].filter(s => s.classList.contains('reveal'));
                    el.style.transitionDelay = (siblings.indexOf(el) * 90) + 'ms';
                    el.classList.add('in');
                    io.unobserve(el);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            document.querySelectorAll('.reveal').forEach(el => io.observe(el));
        } else {
            // reduced motion: no js-anim class added, so .reveal is already visible; just fill counters.
            document.querySelectorAll('.count').forEach(el => el.textContent = (+el.dataset.to).toLocaleString('id-ID'));
        }
    </script>
</body>
</html>
