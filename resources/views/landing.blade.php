<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A private collection of LEGO sets, catalogued set by set.">

    {{-- Nothing here is meant to be found: the collection is private and the
         only thing to do on this page is sign in. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name') }} — A LEGO set collection</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fredoka:500,600|outfit:400,500,600" rel="stylesheet">

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cg fill='%23d01012'%3E%3Crect x='7' y='6' width='7' height='4.5' rx='1.4'/%3E%3Crect x='18' y='6' width='7' height='4.5' rx='1.4'/%3E%3Crect x='4' y='10' width='24' height='15' rx='2.4'/%3E%3C/g%3E%3C/svg%3E">

    <style>
        :root {
            --plate: #dfe3e9;
            --ink: #15171c;
            --muted: #5b6170;
            --faint: #878d9b;

            --red: #d01012;
            --red-edge: #9a0b0d;
            --blue: #0b64b0;
            --blue-edge: #07487f;
            --yellow: #f5b50a;
            --yellow-edge: #bd8a00;

            --stud: 30px;   /* pitch of the baseplate studs */
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            gap: 2rem;

            /* The page is a baseplate: a field of studs, lit from above so
               each one has a highlight on top and a shadow beneath. */
            background-color: var(--plate);
            background-image:
                radial-gradient(circle at 50% 42%, rgba(255, 255, 255, 0.7) 20%, transparent 22%),
                radial-gradient(circle at 50% 58%, rgba(0, 0, 0, 0.07) 20%, transparent 22%);
            background-size: var(--stud) var(--stud);
            background-position: 0 0;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .lockup {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }

        .lockup svg { width: 30px; height: 30px; display: block; }

        .wordmark {
            font-family: 'Fredoka', 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .badge {
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            background: rgba(255, 255, 255, 0.75);
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* The content sits on a plate of its own, studs along the top edge. */
        .plate {
            position: relative;
            width: 100%;
            max-width: 46rem;
            background: #fff;
            border-radius: 1.25rem;
            padding: 2.5rem 1.75rem 2rem;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 10px 0 -2px rgba(0, 0, 0, 0.06),
                0 22px 45px -20px rgba(16, 24, 40, 0.45);
        }

        /* Studs: a squared tab per tile, repeated across the edge, on a strip
           sitting just above its plate so they read as part of it. Each is a
           little over half the pitch wide — spaced any wider they stop
           looking like studs and start looking like a perforated edge — and
           the shadow gives them an edge against the baseplate behind. */
        .plate::before,
        .brick::before {
            content: "";
            position: absolute;
            left: 1.75rem;
            right: 1.75rem;
            top: -13px;
            height: 13px;
            /* "space", not "repeat-x": the strip is rarely an exact multiple
               of the stud pitch, and repeating would clip the last one in
               half against the edge. This lays down whole studs only and
               shares the remainder out as gaps between them. */
            background-repeat: space no-repeat;
            background-position: bottom left;
            background-size: 42px 100%;
            filter: drop-shadow(0 -1px 1.5px rgba(16, 24, 40, 0.14));
        }

        .plate::before {
            background-image: linear-gradient(to right, transparent 0 8px, #fff 8px 34px, transparent 34px);
        }

        h1 {
            font-family: 'Fredoka', 'Outfit', sans-serif;
            font-weight: 600;
            font-size: clamp(2.1rem, 6.4vw, 3.5rem);
            line-height: 1.08;
            letter-spacing: -0.01em;
            max-width: 14ch;
        }

        h1 em {
            font-style: normal;
            color: var(--red);
        }

        .lede {
            margin-top: 1rem;
            font-size: clamp(1rem, 2.4vw, 1.15rem);
            color: var(--muted);
            max-width: 46ch;
            line-height: 1.55;
        }

        .lede b {
            color: var(--ink);
            font-weight: 600;
        }

        /* A button with a brick's edge under it, so it presses. */
        .cta {
            margin-top: 1.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            background: var(--red);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.85rem 1.75rem;
            border-radius: 0.7rem;
            box-shadow: 0 5px 0 var(--red-edge);
            transition: transform 0.06s ease, box-shadow 0.06s ease, background 0.15s ease;
        }

        .cta:hover { background: #e01416; }

        .cta:active {
            transform: translateY(4px);
            box-shadow: 0 1px 0 var(--red-edge);
        }

        .cta:focus-visible {
            outline: 3px solid var(--blue);
            outline-offset: 3px;
        }

        .cta svg { width: 17px; height: 17px; }

        .bricks {
            margin-top: 2.75rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.75rem 1rem;
        }

        /* Three across once a six-figure piece count has room to be read;
           stacked below that rather than shrunk to fit. */
        @media (min-width: 34rem) {
            .bricks { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .brick {
            position: relative;
            border-radius: 0.6rem;
            padding: 1.1rem 1rem;
            text-align: center;
            color: #fff;
        }

        .brick::before {
            left: 0.9rem;
            right: 0.9rem;
            top: -10px;
            height: 10px;
            background-size: 34px 100%;
        }

        .brick dd {
            font-family: 'Fredoka', 'Outfit', sans-serif;
            font-weight: 600;
            font-size: clamp(1.6rem, 5.2vw, 2.1rem);
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .brick dt {
            margin-top: 0.2rem;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .brick--red { background: var(--red); box-shadow: 0 5px 0 var(--red-edge); }
        .brick--red::before { background-image: linear-gradient(to right, transparent 0 7px, var(--red) 7px 27px, transparent 27px); }

        .brick--blue { background: var(--blue); box-shadow: 0 5px 0 var(--blue-edge); }
        .brick--blue::before { background-image: linear-gradient(to right, transparent 0 7px, var(--blue) 7px 27px, transparent 27px); }

        /* White on yellow is unreadable, so this one takes dark type — which
           is how the real brick is printed on anyway. */
        .brick--yellow { background: var(--yellow); box-shadow: 0 5px 0 var(--yellow-edge); color: var(--ink); }
        .brick--yellow::before { background-image: linear-gradient(to right, transparent 0 7px, var(--yellow) 7px 27px, transparent 27px); }

        footer {
            text-align: center;
            color: var(--faint);
            font-size: 0.78rem;
            display: grid;
            gap: 0.4rem;
        }

        footer .legal { font-size: 0.7rem; color: #949aa7; }

        @media (min-width: 40rem) {
            body { padding: 2rem 2.5rem 2.5rem; }
            .plate { padding: 3.5rem 3rem 2.75rem; }
            .plate::before { left: 2.5rem; right: 2.5rem; }
        }
    </style>
</head>

<body>
    <header>
        <span class="lockup">
            <svg viewBox="0 0 32 32" aria-hidden="true">
                <g fill="var(--red)">
                    <rect x="7" y="6" width="7" height="4.5" rx="1.4" />
                    <rect x="18" y="6" width="7" height="4.5" rx="1.4" />
                    <rect x="4" y="10" width="24" height="15" rx="2.4" />
                </g>
            </svg>
            <span class="wordmark">{{ config('app.name') }}</span>
        </span>

        <span class="badge">Private collection</span>
    </header>

    <main>
        <div class="plate">
            <h1>A <em>LEGO</em> set collection.</h1>

            @php
                // Built here rather than with an inline @if: a directive
                // attached to the end of a word is not one, and splitting the
                // sentence across directives would strand whitespace before
                // its punctuation. Raw-echoed below, which is safe because
                // both halves are cast to integers.
                $span = $figures['earliest_set'] && $figures['latest_set']
                    ? ', from <b>'.(int) $figures['earliest_set'].'</b> to <b>'.(int) $figures['latest_set'].'</b>'
                    : '';
            @endphp

            @if ($figures['sets'])
                <p class="lede">
                    <b>{{ number_format($figures['copies']) }}</b> copies of
                    <b>{{ number_format($figures['sets']) }}</b> sets across
                    <b>{{ number_format($figures['themes']) }}</b> themes{!! $span !!}.
                </p>
            @endif

            <a class="cta" href="{{ route('filament.admin.auth.login') }}">
                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <rect x="3.5" y="7" width="9" height="6.5" rx="1.4" stroke="currentColor" stroke-width="1.6" />
                    <path d="M5.5 7V5.2a2.5 2.5 0 0 1 5 0V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                Sign in
            </a>

            @if ($figures['sets'])
                <dl class="bricks">
                    <div class="brick brick--red">
                        <dd>{{ number_format($figures['sets']) }}</dd>
                        <dt>Sets</dt>
                    </div>
                    <div class="brick brick--blue">
                        <dd>{{ number_format($figures['pieces']) }}</dd>
                        <dt>Pieces</dt>
                    </div>
                    <div class="brick brick--yellow">
                        <dd>{{ number_format($figures['minifigures']) }}</dd>
                        <dt>Minifigures</dt>
                    </div>
                </dl>
            @endif
        </div>
    </main>

    <footer>
        <p>Catalogued set by set{{ $figures['collecting_since'] ? ' since '.(int) $figures['collecting_since'] : '' }}.</p>
        <p class="legal">LEGO® is a trademark of the LEGO Group, which does not sponsor, authorise or endorse this site.</p>
    </footer>
</body>

</html>
