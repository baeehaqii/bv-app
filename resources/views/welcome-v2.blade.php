@php
    $data = json_decode(file_get_contents(resource_path('data/beyond-viral-content.json')), true);
    $c = $data['company'];
    $contact = $data['contact'];
    $li = $data['linkedin'];
    $wa = $contact['whatsapp'];
    $slug = fn($n) => strtolower(preg_replace('/[^a-z0-9]+/i','-',$n));
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $c['name'] }} — {{ $c['positioning'] }}</title>
    <meta name="description" content="{{ $c['description_id'] }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=Caveat:wght@600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.2/lib/anime.min.js"></script>

    <style>
        :root {
            --blue: #2D4CF5;
            --lime: #DAFF00;
            --purple: #6D4AFF;
            --ink: #0E0E12;
            --cream: #F4F1E9;
            --pink: #FF9FD2;
            --teal: #8FE7D8;
            --green: #C6F24E;
            --gray: #E2DED3;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--cream); color: var(--ink);
            -webkit-font-smoothing: antialiased; overflow-x: hidden;
        }
        h1,h2,h3,.display { font-family:'Space Grotesk',system-ui,sans-serif; letter-spacing:-.02em; }

        .container { width:100%; max-width:1180px; margin:0 auto; padding:0 22px; }
        .muted { color:#5A5A66; }

        /* full-bleed sections, edge to edge */
        section { padding: clamp(56px,8vw,110px) 0; position:relative; }
        .sec-cream { background: var(--cream); }
        .sec-blue  { background: var(--blue); color:#fff; }
        .sec-blue .muted { color: rgba(255,255,255,.78); }
        .sec-ink   { background: var(--ink); color:#fff; }
        .sec-ink .muted { color: rgba(255,255,255,.7); }
        .sec-gray  { background: var(--gray); }
        .sec-blue::before, .sec-ink::before {
            content:''; position:absolute; inset:0; pointer-events:none; opacity:.6;
            background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);
            background-size:46px 46px;
        }
        .sec-inner { position:relative; z-index:1; }

        /* buttons */
        a.btn,button.btn { display:inline-flex; align-items:center; gap:8px; cursor:pointer; text-decoration:none;
            font-family:'Space Grotesk'; font-weight:600; font-size:15px; padding:13px 24px; border-radius:999px;
            transition:transform .18s,box-shadow .18s,background .18s; min-height:46px; border:none; }
        .btn-purple { background:var(--purple); color:#fff; } .btn-purple:hover { transform:translateY(-2px); box-shadow:0 12px 24px -8px rgba(109,74,255,.6); }
        .btn-ink { background:var(--ink); color:#fff; } .btn-ink:hover { transform:translateY(-2px); }
        .btn-lime { background:var(--lime); color:var(--ink); } .btn-lime:hover { transform:translateY(-2px); box-shadow:0 12px 24px -8px rgba(218,255,0,.7); }
        .btn-outline { background:transparent; color:var(--ink); border:1.5px solid var(--ink); } .btn-outline:hover { background:var(--ink); color:#fff; }
        .sec-blue .btn-outline, .sec-ink .btn-outline { color:#fff; border-color:#fff; } .sec-blue .btn-outline:hover { background:#fff; color:var(--ink); }
        :focus-visible { outline:3px solid var(--purple); outline-offset:2px; }

        .mark { position:relative; white-space:nowrap; }
        .mark::before { content:''; position:absolute; left:-4px; right:-4px; bottom:6%; height:42%; background:var(--lime); z-index:-1; border-radius:4px; transform:rotate(-1deg); }
        .display .mark { z-index:0; } .display .mark span { position:relative; z-index:1; }

        .sticker { font-family:'Caveat'; font-size:22px; font-weight:700; color:var(--ink); display:inline-block;
            padding:4px 14px; border:1.5px solid var(--ink); border-radius:999px; transform:rotate(-3deg); background:#fff; }

        /* nav (sticky, full width) */
        .navbar { position:sticky; top:14px; z-index:50; margin:14px auto 0; max-width:1180px; }
        .nav { display:flex; align-items:center; justify-content:space-between; gap:16px; background:#fff;
            border-radius:999px; padding:9px 9px 9px 18px; border:1.5px solid var(--ink); margin:0 22px; }
        .nav-links { display:none; gap:24px; }
        .nav-links a { color:var(--ink); text-decoration:none; font-weight:500; font-size:15px; opacity:.85; }
        .nav-links a:hover { opacity:1; color:var(--purple); }
        @media (min-width:880px){ .nav-links{display:flex;} }

        .eyebrow { font-family:'Space Grotesk'; font-size:13px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:var(--purple); }
        .sec-blue .eyebrow,.sec-ink .eyebrow { color:var(--lime); }
        h2.title { font-size:clamp(30px,5.5vw,56px); font-weight:700; line-height:1.02; margin:12px 0 0; }
        .lead { font-size:clamp(16px,2vw,18px); line-height:1.6; max-width:560px; }

        .grid { display:grid; gap:20px; }
        @media (min-width:760px){ .c2{grid-template-columns:repeat(2,1fr);} .c3{grid-template-columns:repeat(3,1fr);} .c4{grid-template-columns:repeat(4,1fr);} }

        .ccard { border-radius:22px; padding:26px; border:1.5px solid var(--ink); position:relative; }
        .ccard h3 { margin:0 0 8px; font-size:19px; }
        .ccard p { margin:0; font-size:14.5px; line-height:1.55; }
        .ccard .tag { font-family:'Space Grotesk'; font-size:12px; font-weight:600; background:var(--ink); color:#fff; padding:4px 11px; border-radius:999px; display:inline-block; margin-bottom:14px; }
        .bg-lime{background:var(--green);} .bg-pink{background:var(--pink);} .bg-teal{background:var(--teal);} .bg-orange{background:#FF7A2F;}
        .bg-purple{background:var(--purple);color:#fff;} .bg-purple .tag{background:#fff;color:var(--ink);} .bg-purple p{color:rgba(255,255,255,.85);}
        .bg-white{background:#fff;}

        .num { font-family:'Space Grotesk'; font-weight:700; font-size:clamp(40px,6vw,64px); line-height:1; }

        .feature-panel { background:var(--purple); border-radius:26px; padding:clamp(22px,4vw,40px); color:#fff; }
        .feature-panel .ico { width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,.16); display:grid; place-items:center; color:var(--lime); margin-bottom:14px; }

        /* partner logos */
        .logo-row { display:flex; flex-wrap:wrap; gap:14px; justify-content:center; }
        .logo-chip { display:flex; align-items:center; justify-content:center; width:150px; height:88px; background:#fff;
            border:1.5px solid var(--ink); border-radius:16px; padding:16px; transition:transform .18s; }
        .logo-chip:hover { transform:translateY(-4px) rotate(-1deg); }
        .logo-chip img { max-width:100%; max-height:52px; width:auto; height:auto; object-fit:contain; }

        /* work / portfolio cards */
        .work { border-radius:22px; overflow:hidden; border:1.5px solid var(--ink); }
        .work .thumb { aspect-ratio:16/10; display:grid; place-items:center; padding:30px; }
        .work .body { background:#fff; padding:20px 22px; }
        .work h3 { font-size:20px; margin:0 0 6px; }
        .work p { margin:0; font-size:14.5px; color:#5A5A66; line-height:1.5; }
        .work .thumb .label { font-family:'Space Grotesk'; font-weight:700; font-size:clamp(26px,3vw,40px); text-align:center; }

        /* rating card */
        .rating { background:var(--ink); color:#fff; border-radius:22px; padding:30px; text-align:center; border:1.5px solid var(--ink); }
        .rating .big { font-family:'Space Grotesk'; font-weight:700; font-size:54px; line-height:1; }
        .stars { color:var(--lime); letter-spacing:3px; font-size:18px; margin:8px 0; }

        /* faq */
        details { border-bottom:1.5px solid rgba(14,14,18,.14); padding:18px 4px; }
        details summary { cursor:pointer; list-style:none; font-family:'Space Grotesk'; font-weight:600; font-size:17px; display:flex; justify-content:space-between; gap:12px; }
        details summary::-webkit-details-marker { display:none; }
        details summary::after { content:'↗'; font-size:20px; transition:transform .2s; }
        details[open] summary::after { transform:rotate(45deg); }
        details p { margin:12px 0 0; color:#5A5A66; line-height:1.6; }
        .help-card { background:var(--lime); border:1.5px solid var(--ink); border-radius:22px; padding:30px; box-shadow:6px 6px 0 var(--ink); }

        /* contact form */
        .form-wrap { background:var(--green); border:1.5px solid var(--ink); border-radius:24px; padding:clamp(26px,4vw,44px); }
        .form-wrap input, .form-wrap textarea { width:100%; background:transparent; border:none; border-bottom:1.5px solid rgba(14,14,18,.4);
            padding:12px 2px; font:inherit; font-size:15px; color:var(--ink); outline:none; }
        .form-wrap input:focus, .form-wrap textarea:focus { border-color:var(--ink); }
        .form-wrap label { font-size:13px; font-weight:600; display:block; margin-top:14px; margin-bottom:2px; }
        .form-wrap input::placeholder, .form-wrap textarea::placeholder { color:#7a7a82; }

        /* footer */
        .foot { background:var(--purple); color:#fff; position:relative; overflow:hidden; }
        .foot a { color:rgba(255,255,255,.82); text-decoration:none; } .foot a:hover { color:var(--lime); }
        .wordmark { font-family:'Space Grotesk'; font-weight:700; font-size:clamp(64px,18vw,230px); line-height:.85; letter-spacing:-.04em; text-align:center; color:rgba(255,255,255,.18); }
        .blob { position:absolute; border-radius:42% 58% 60% 40%/50% 45% 55% 50%; }
        .soc { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.16); display:grid; place-items:center; color:#fff; }
        .soc:hover { background:var(--lime); color:var(--ink); }

        .reveal { opacity:1; }
        .js-anim .reveal { opacity:0; transform:translateY(26px); transition:opacity .6s cubic-bezier(.16,1,.3,1),transform .6s cubic-bezier(.16,1,.3,1); }
        .js-anim .reveal.in { opacity:1; transform:none; }
        @media (prefers-reduced-motion:reduce){ .reveal{opacity:1!important;transform:none!important;} *{scroll-behavior:auto!important;} }

        @media (max-width:600px){
            .ccard{padding:22px;} .num{font-size:40px;}
            .logo-chip{width:calc(50% - 7px);}
            .wordmark{ -webkit-text-stroke:0; }
        }
    </style>
</head>
<body>

    {{-- ===== NAV (sticky) ===== --}}
    <div class="navbar" id="hero-nav">
        <nav class="nav">
            <a href="#" style="display:flex;align-items:center;gap:10px;text-decoration:none"><img src="/images/logo_bv.png" alt="{{ $c['name'] }}" style="height:26px"></a>
            <div class="nav-links">
                <a href="#about">About</a><a href="#service">Services</a><a href="#work">Work</a><a href="#faq">FAQ</a><a href="#contact">Contact</a>
            </div>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-purple">Contact Us</a>
        </nav>
    </div>

    {{-- ===== HERO ===== --}}
    <section class="sec-cream" style="padding-top:clamp(40px,6vw,70px)">
        <div class="container sec-inner" style="text-align:center">
            <h1 class="display" id="hero-title" style="font-size:clamp(40px,8.5vw,96px);font-weight:700;line-height:1.0;margin:0 auto;max-width:14ch">
                Bikin <span class="mark"><span>ideas</span></span> jadi <span class="mark"><span>viral</span></span> experiences
            </h1>
            <p class="lead muted" id="hero-sub" style="margin:26px auto 0">{{ $data['hero']['body_id'] }}</p>
            <div id="hero-cta" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:30px">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-ink">{{ $data['hero']['cta']['label'] }} →</a>
                <a href="#service" class="btn btn-outline">Lihat Layanan</a>
            </div>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:36px">
                @foreach(['Story Orchestration','Execution Excellence','Real Time Insight'] as $i => $b)
                    <span class="sticker" style="{{ $i===1 ? 'background:var(--lime);' : '' }}">{{ $b }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== PARTNER ===== --}}
    <section class="sec-ink" style="padding:clamp(40px,5vw,60px) 0">
        <div class="container sec-inner reveal">
            <p class="muted" style="text-align:center;font-size:13px;letter-spacing:2px;text-transform:uppercase;margin:0 0 22px">{{ $data['partners']['heading_id'] }}</p>
            <div class="logo-row">
                @foreach($data['partners']['clients'] as $p)
                    <div class="logo-chip"><img src="/images/partners/{{ $slug($p['name']) }}.png" alt="{{ $p['name'] }}" loading="lazy"></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== WHY BRANDS TRUST US (blue stats panel) ===== --}}
    <section class="sec-blue" id="about">
        <div class="container sec-inner">
            <div class="reveal" style="text-align:center;max-width:680px;margin:0 auto 44px">
                <h2 class="title">Kenapa brand percaya <span class="mark"><span>Beyond Viral</span></span></h2>
                <p class="lead muted" style="margin:18px auto 0">{{ $c['description_en'] }}</p>
            </div>
            <div class="grid c2 reveal" style="align-items:stretch">
                <div class="feature-panel" style="background:rgba(255,255,255,.10);border:1.5px solid rgba(255,255,255,.25)">
                    <span class="sticker" style="background:var(--lime)">Why choose us</span>
                    <h3 style="font-family:'Space Grotesk';font-size:clamp(24px,3vw,32px);margin:18px 0 10px">Design & campaign yang bikin dampak</h3>
                    <p class="muted">{{ $c['description_id'] }}</p>
                </div>
                <div class="grid c2" style="gap:18px">
                    <div class="ccard bg-lime"><span class="tag">Network</span><div class="num">20K+</div><p style="margin-top:10px">Creators siap eksekusi.</p></div>
                    <div class="ccard bg-pink"><span class="tag">Clients</span><div class="num">{{ count($data['partners']['clients']) }}+</div><p style="margin-top:10px">Brand yang percaya kami.</p></div>
                    <div class="ccard bg-teal" style="grid-column:1 / -1"><span class="tag">Eksekusi</span><div class="num">100%</div><p style="margin-top:10px">Full-service: brief, konten, sampai report — semua kami urus.</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== SERVICE ===== --}}
    <section class="sec-cream" id="service">
        <div class="container sec-inner">
            <div class="reveal" style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:18px;margin-bottom:40px">
                <div style="max-width:560px">
                    <span class="sticker">Our services</span>
                    <h2 class="title" style="margin-top:16px">{{ $data['differentiators']['heading_id'] }}</h2>
                </div>
                <p class="muted" style="max-width:360px">{{ $data['differentiators']['intro_id'] }}</p>
            </div>
            <div class="grid c3 reveal">
                @foreach($data['differentiators']['items'] as $i => $item)
                    <div class="feature-panel">
                        <div class="ico">
                            @if($i===0)<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>
                            @elseif($i===1)<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                            @else<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>@endif
                        </div>
                        <h3 style="font-family:'Space Grotesk';font-size:20px;margin:0 0 8px">{{ $item['title'] }}</h3>
                        <p style="font-weight:600;margin:0 0 6px">{{ $item['subtitle_id'] }}</p>
                        <p style="color:rgba(255,255,255,.8);font-size:14.5px;line-height:1.55;margin:0">{{ $item['body_id'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== WORK / PORTFOLIO ===== --}}
    <section class="sec-gray" id="work">
        <div class="container sec-inner">
            <div class="reveal" style="margin-bottom:40px">
                <span class="sticker">Our work</span>
                <h2 class="title" style="margin-top:14px">Ideas yang kami <span class="mark"><span>bikin viral</span></span></h2>
            </div>
            @php
                $works = [
                    ['t'=>'Beauty Brand Campaign','d'=>'KOL campaign untuk large beauty brand — ide kreatif nyambung sama eksekusi.','bg'=>'bg-pink','tag'=>'Beauty'],
                    ['t'=>'FMCG Mom & Kids','d'=>'Konsisten dari ide, eksekusi, sampai negosiasi untuk brand mom & kids.','bg'=>'bg-lime','tag'=>'FMCG'],
                    ['t'=>'Fintech e-Wallet','d'=>'Cost per install turun drastis lewat creative briefing performance campaign.','bg'=>'bg-teal','tag'=>'Fintech'],
                    ['t'=>'Livestream & Produksi','d'=>'Livestream capabilities + digital production untuk brand yang mau scale.','bg'=>'bg-purple','tag'=>'Production'],
                ];
            @endphp
            <div class="grid c2 reveal">
                @foreach($works as $w)
                    <div class="work">
                        <div class="thumb {{ $w['bg'] }}">
                            <div class="label">{{ $w['t'] }}</div>
                        </div>
                        <div class="body">
                            <span class="ccard" style="border:none;padding:0"><span class="tag" style="background:var(--ink);color:#fff;font-family:'Space Grotesk';font-size:12px;font-weight:600;padding:4px 11px;border-radius:999px">{{ $w['tag'] }}</span></span>
                            <h3 style="margin-top:12px">{{ $w['t'] }}</h3>
                            <p>{{ $w['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== PAIN POINT ===== --}}
    <section class="sec-cream">
        <div class="container sec-inner">
            <div class="reveal" style="text-align:center;max-width:620px;margin:0 auto 40px">
                <span class="eyebrow">Pain Point</span>
                <h2 class="title">{{ $data['pain_points']['heading_id'] }}</h2>
            </div>
            <div class="grid c3 reveal">
                @foreach($data['pain_points']['items'] as $i => $item)
                    <div class="ccard bg-white"><span class="tag" style="background:var(--purple)">0{{ $i+1 }}</span><h3>{{ $item['title_id'] }}</h3><p class="muted">{{ $item['body_id'] }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== PEMBEDA / PROCESS ===== --}}
    <section class="sec-blue" id="pembeda">
        <div class="container sec-inner">
            <div class="reveal" style="text-align:center;max-width:660px;margin:0 auto 40px">
                <span class="sticker">How we work</span>
                <h2 class="title" style="margin-top:14px">{{ $data['process']['heading_id'] }}</h2>
                <p class="lead muted" style="margin:16px auto 0">{{ $data['process']['intro_id'] }}</p>
            </div>
            <div class="grid c4 reveal">
                @php $cols=['bg-lime','bg-pink','bg-teal','bg-orange']; @endphp
                @foreach($data['process']['steps'] as $i => $step)
                    <div class="ccard {{ $cols[$i] }}"><div class="num" style="font-size:40px">0{{ $i+1 }}</div><h3 style="margin-top:12px">{{ $step['title_id'] }}</h3><p>{{ $step['body_id'] }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== TESTIMONIALS (rating + cards) ===== --}}
    <section class="sec-gray">
        <div class="container sec-inner">
            <div class="reveal" style="text-align:center;margin-bottom:40px">
                <span class="sticker">Testimonials</span>
                <h2 class="title" style="margin-top:14px">Kata mereka yang udah <span class="mark"><span>ngerasain</span></span></h2>
            </div>
            <div class="grid reveal" style="grid-template-columns:1fr;gap:20px">
                <div class="grid c3" style="gap:20px">
                    <div class="rating">
                        <div class="big">4.9</div>
                        <div class="stars">★★★★★</div>
                        <div style="font-size:13px;color:rgba(255,255,255,.7)">dari brand partner</div>
                    </div>
                    @foreach(array_slice($data['testimonials'],0,2) as $t)
                        <div class="ccard bg-white">
                            <div style="display:flex;justify-content:space-between;align-items:center"><span class="tag" style="background:#fff;color:var(--ink);border:1.5px solid var(--ink)">{{ $t['company'] }}</span><span style="color:#F5A623">★★★★★</span></div>
                            <p style="margin-top:14px;font-size:15px;line-height:1.65">{{ $t['quote_id'] }}</p>
                            <div style="margin-top:16px;display:flex;align-items:center;gap:12px">
                                <div style="width:38px;height:38px;border-radius:50%;background:var(--purple)"></div>
                                <div style="font-weight:600;font-size:14px">{{ $t['author'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FAQ ===== --}}
    <section class="sec-cream" id="faq">
        <div class="container sec-inner">
            <div class="grid c2" style="align-items:start;gap:44px">
                <div class="reveal">
                    <span class="sticker" style="background:var(--lime)">FAQs</span>
                    <h2 class="title" style="margin-top:14px">Frequently asked questions</h2>
                    <div style="margin-top:22px">
                        <details open><summary>Apa bedanya BV sama KOL agency lain?</summary><p>{{ $data['differentiators']['intro_id'] }}</p></details>
                        <details><summary>Berapa lama dapat proposal?</summary><p>3-5 hari kerja setelah kamu share brief.</p></details>
                        <details><summary>Platform apa aja yang dihandle?</summary><p>{{ implode(', ', $li['specialties']) }}.</p></details>
                        <details><summary>Apakah ada laporan campaign?</summary><p>Ada, real-time — data, insight & ROI langsung di tangan kamu.</p></details>
                        <details><summary>Cocok untuk startup / brand kecil?</summary><p>Cocok. Setiap campaign dirancang custom sesuai objektif & budget brand kamu.</p></details>
                    </div>
                </div>
                <div class="reveal help-card" style="align-self:start;margin-top:40px">
                    <div class="display" style="font-size:60px;line-height:1">?</div>
                    <p style="font-weight:600;font-size:18px;margin:14px 0 0">Masih ada pertanyaan? Kami siap bantu kamu mulai campaign berikutnya.</p>
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-purple" style="margin-top:20px">Tanya sekarang →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== OFFER ===== --}}
    <section class="sec-ink" id="offer">
        <div class="container sec-inner reveal" style="text-align:center;max-width:760px">
            <span class="sticker" style="background:var(--lime)">{{ $data['offer']['badge_id'] }}</span>
            <h2 class="title" style="margin-top:18px">{{ $data['offer']['headline_id'] }}</h2>
            <p style="font-size:clamp(17px,2.4vw,22px);font-weight:600;margin:12px 0 0">{{ $data['offer']['body_id'] }}</p>
            <p style="color:var(--lime);font-family:'Space Grotesk';font-weight:700;margin:14px 0 0">{{ $data['offer']['urgency_id'] }}</p>
            <p class="muted" style="max-width:620px;margin:14px auto 0">{{ $data['offer']['rationale_id'] }}</p>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-lime" style="margin-top:24px">{{ $data['offer']['cta']['label'] }} →</a>
        </div>
    </section>

    {{-- ===== CONTACT FORM ===== --}}
    <section class="sec-cream" id="contact">
        <div class="container sec-inner">
            <div class="grid c2" style="gap:24px;align-items:stretch">
                <div class="form-wrap reveal" style="display:flex;flex-direction:column;justify-content:space-between">
                    <div>
                        <span class="sticker">Contact us</span>
                        <h2 class="display" style="font-size:clamp(26px,3.5vw,40px);font-weight:700;margin:18px 0 0">Mau brand kamu jadi viral berikutnya?</h2>
                        <p style="margin-top:12px;font-size:16px;line-height:1.6">Share brief kamu, kita rancang strategi & eksekusi bareng.</p>
                    </div>
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-ink" style="margin-top:24px;align-self:flex-start">{{ $contact['phone'] }} →</a>
                </div>
                {{-- Mailto form: lazy, no backend. ponytail: native form action=mailto --}}
                <form class="reveal ccard bg-white" action="mailto:{{ $contact['email'] }}" method="post" enctype="text/plain" style="display:flex;flex-direction:column">
                    <div class="grid c2" style="gap:14px">
                        <div style="display:flex;flex-direction:column"><label for="fn">Nama</label><input id="fn" name="Nama" placeholder="Nama kamu" required></div>
                        <div style="display:flex;flex-direction:column"><label for="brand">Brand</label><input id="brand" name="Brand" placeholder="Nama brand"></div>
                    </div>
                    <label for="em">Email</label><input id="em" type="email" name="Email" placeholder="email@brand.com" required>
                    <label for="msg">Pesan</label><textarea id="msg" name="Pesan" rows="3" placeholder="Ceritain campaign kamu..."></textarea>
                    <button class="btn btn-purple" type="submit" style="margin-top:22px;align-self:flex-start">Kirim pesan →</button>
                </form>
            </div>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer class="foot">
        <span class="blob" style="width:90px;height:90px;background:var(--teal);left:6%;bottom:18%"></span>
        <span class="blob" style="width:70px;height:70px;background:var(--pink);right:18%;bottom:24%"></span>
        <span class="blob" style="width:60px;height:60px;background:#FF9F2F;right:7%;bottom:14%"></span>
        <div class="container sec-inner" style="padding-top:clamp(40px,6vw,72px);padding-bottom:30px;position:relative;z-index:2">
            <div class="grid c4" style="gap:26px">
                <div>
                    <img src="/images/logo_bv.png" alt="{{ $c['name'] }}" style="height:30px;margin-bottom:14px">
                    <p style="color:rgba(255,255,255,.78);max-width:280px;line-height:1.6">{{ $c['description_id'] }}</p>
                    <div style="display:flex;gap:10px;margin-top:16px">
                        <a class="soc" href="{{ $li['url'] }}" target="_blank" rel="noopener" aria-label="LinkedIn"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M4.98 3.5a2.5 2.5 0 11-.02 5 2.5 2.5 0 01.02-5zM3 9h4v12H3zM9 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C20.4 8.65 21 11 21 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3s-2.3 1.57-2.3 3.2V21H9z"/></svg></a>
                        <a class="soc" href="{{ $wa }}" target="_blank" rel="noopener" aria-label="WhatsApp"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15l-1 4 4.1-1A10 10 0 1012 2zm5.5 14.2c-.2.6-1.2 1.2-1.7 1.2-.4 0-1 .1-3.3-.9-2.8-1.2-4.5-4-4.6-4.2-.1-.2-1-1.4-1-2.6s.6-1.8.9-2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.5c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2 1.3 2.3 1.5.2.1.4.1.6-.1l.7-.9c.2-.3.4-.2.6-.1l1.9.9c.2.1.4.2.4.3.1.1.1.7-.1 1.3z"/></svg></a>
                        <a class="soc" href="mailto:{{ $contact['email'] }}" aria-label="Email"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></a>
                    </div>
                </div>
                <div><div style="font-family:'Space Grotesk';font-weight:600;margin-bottom:12px">Navigasi</div><div style="display:flex;flex-direction:column;gap:9px"><a href="#about">About</a><a href="#service">Services</a><a href="#work">Work</a><a href="#offer">Offer</a></div></div>
                <div><div style="font-family:'Space Grotesk';font-weight:600;margin-bottom:12px">Services</div><div style="display:flex;flex-direction:column;gap:9px">@foreach(array_slice($li['specialties'],0,5) as $s)<a href="#service">{{ $s }}</a>@endforeach</div></div>
                <div><div style="font-family:'Space Grotesk';font-weight:600;margin-bottom:12px">Kontak</div><div style="display:flex;flex-direction:column;gap:9px"><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a><a href="tel:{{ $contact['phone'] }}">{{ $contact['phone'] }}</a><span style="color:rgba(255,255,255,.6);font-size:13px;line-height:1.5">{{ $contact['address'] }}</span></div></div>
            </div>
            <div class="wordmark" style="margin-top:30px">BEYOND VIRAL</div>
            <div style="color:rgba(255,255,255,.6);padding-top:18px;border-top:1px solid rgba(255,255,255,.18);font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:10px">
                <span>{{ $c['copyright'] }}</span><span>{{ $c['legal_name'] }}</span>
            </div>
        </div>
    </footer>

    <script>
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reduce) document.documentElement.classList.add('js-anim');

        if (!reduce && window.anime) {
            anime.timeline({ easing:'easeOutExpo' })
                .add({ targets:'#hero-nav', opacity:[0,1], translateY:[-14,0], duration:600 })
                .add({ targets:'#hero-title', opacity:[0,1], translateY:[24,0], duration:900 }, '-=300')
                .add({ targets:'#hero-sub', opacity:[0,1], translateY:[18,0], duration:600 }, '-=550')
                .add({ targets:'#hero-cta', opacity:[0,1], translateY:[16,0], duration:500 }, '-=400')
                .add({ targets:'.sticker', opacity:[0,1], scale:[.8,1], delay:anime.stagger(70), duration:450 }, '-=200');

            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (!e.isIntersecting) return;
                    const el = e.target;
                    const sibs = [...el.parentElement.children].filter(s => s.classList.contains('reveal'));
                    el.style.transitionDelay = (sibs.indexOf(el)*80) + 'ms';
                    el.classList.add('in'); io.unobserve(el);
                });
            }, { threshold:0.1, rootMargin:'0px 0px -5% 0px' });
            document.querySelectorAll('.reveal').forEach(el => io.observe(el));
        }
    </script>
</body>
</html>
