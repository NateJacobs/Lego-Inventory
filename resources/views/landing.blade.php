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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cg fill='%23f5b50a'%3E%3Crect x='7' y='6' width='7' height='4.5' rx='1.4'/%3E%3Crect x='18' y='6' width='7' height='4.5' rx='1.4'/%3E%3Crect x='4' y='10' width='24' height='15' rx='2.4'/%3E%3C/g%3E%3C/svg%3E">

    <style>
        :root {
            --bg: #0b0b0f;
            --ink: #f4f1ea;
            --muted: #9a968e;
            --faint: #6b6862;
            --stud: #f5b50a;
            --stud-soft: #ffd466;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            display: flex;
            flex-direction: column;
            min-height: 100svh;
            position: relative;
        }

        /* The glows below hang past the corners, and overflow-x on the body
           will not hold them: once that propagates to the viewport the body
           itself goes back to visible, and the page gained 143px of sideways
           scroll. A layer of its own, sized to the body and clipping its own
           contents, keeps them in without touching how the page scrolls. */
        .glows {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        /* Two soft glows bleeding in from the corners — the primary yellow and
           blue a brick comes in — so the flat canvas has some depth without
           leaning on imagery. */
        .glows::before,
        .glows::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.5;
        }

        .glows::before {
            width: 60vw;
            height: 60vw;
            top: -22vw;
            right: -14vw;
            background: radial-gradient(circle, rgba(245, 181, 10, 0.30), transparent 68%);
        }

        .glows::after {
            width: 55vw;
            height: 55vw;
            bottom: -24vw;
            left: -16vw;
            background: radial-gradient(circle, rgba(0, 87, 168, 0.42), transparent 70%);
        }

        main {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem 1.5rem;
            gap: 1.75rem;
            width: 100%;
            max-width: 52rem;
            margin: 0 auto;
        }

        .lockup {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .lockup svg { width: 28px; height: 28px; display: block; }

        .wordmark {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink);
        }

        h1 {
            font-size: clamp(2.4rem, 7vw, 4.25rem);
            font-weight: 600;
            line-height: 1.05;
            letter-spacing: -0.02em;
            max-width: 16ch;
            background: linear-gradient(180deg, #ffffff 0%, #cfcbc2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat {
            font-size: clamp(0.95rem, 2.2vw, 1.1rem);
            color: var(--muted);
            max-width: 44ch;
        }

        .stat b {
            color: var(--ink);
            font-weight: 600;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.72rem 1.65rem;
            border: 2.5px solid var(--stud);
            border-radius: 999px;
            color: var(--stud-soft);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            background: rgba(245, 181, 10, 0.08);
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .cta:hover {
            background: rgba(245, 181, 10, 0.18);
            border-color: var(--stud-soft);
            color: #ffe6a8;
        }

        .cta svg { width: 15px; height: 15px; }

        .cta:focus-visible {
            outline: 2px solid var(--stud);
            outline-offset: 3px;
        }

        /* Stacked until there is room for three across: a six-figure piece
           count needs about 125px of its own, which a third of a phone's
           width does not have, and shrinking the type to fit would make the
           numbers the smallest thing on a page that exists to show them. */
        .figures {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            width: 100%;
            max-width: 38rem;
            margin-top: 0.75rem;
        }

        @media (min-width: 34rem) {
            .figures { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .figure {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.1rem 0.75rem;
        }

        .figure dd {
            font-size: clamp(1.4rem, 4.5vw, 2rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .figure dt {
            margin-top: 0.3rem;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--faint);
        }

        @media (min-width: 640px) {
            .figures { gap: 1.25rem; }
            .figure { padding: 1.5rem 1rem; }
        }

        .colophon {
            margin-top: 0.75rem;
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .legal {
            position: relative;
            z-index: 1;
            padding: 0 1.5rem 2rem;
            text-align: center;
            font-size: 0.68rem;
            line-height: 1.6;
            color: #4e4c48;
            max-width: 46ch;
            margin: 0 auto;
        }

        /* Quiet entrance, skipped for anyone who asked to reduce motion.
           Filled backwards rather than forwards, so the hidden state belongs
           to the animation instead of to the element: anywhere the animation
           does not run, the content is simply there rather than invisible. */
        @media (prefers-reduced-motion: no-preference) {
            main > * {
                animation: rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) backwards;
            }
            main > *:nth-child(1) { animation-delay: 0.05s; }
            main > *:nth-child(2) { animation-delay: 0.15s; }
            main > *:nth-child(3) { animation-delay: 0.25s; }
            main > *:nth-child(4) { animation-delay: 0.35s; }
            main > *:nth-child(5) { animation-delay: 0.45s; }
            main > *:nth-child(6) { animation-delay: 0.55s; }

            @keyframes rise {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>
</head>

<body>
    <div class="glows" aria-hidden="true"></div>

    <main>
        <span class="lockup" aria-label="{{ config('app.name') }}">
            <svg viewBox="0 0 32 32" aria-hidden="true">
                <defs>
                    <linearGradient id="brick" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#ffd466" />
                        <stop offset="1" stop-color="#f5b50a" />
                    </linearGradient>
                </defs>
                <g fill="url(#brick)">
                    <rect x="7" y="6" width="7" height="4.5" rx="1.4" />
                    <rect x="18" y="6" width="7" height="4.5" rx="1.4" />
                    <rect x="4" y="10" width="24" height="15" rx="2.4" />
                </g>
            </svg>
            <span class="wordmark">{{ config('app.name') }}</span>
        </span>

        <h1>A <i>LEGO</i><br>set&nbsp;collection.</h1>

        @php
            // Built here rather than with inline @if: a directive attached to
            // the end of a word is not one, and splitting the sentence over
            // directives would put stray whitespace before its punctuation.
            // Raw-echoed below, which is safe because both halves are integers.
            $span = $figures['earliest_set'] && $figures['latest_set']
                ? ', from <b>'.(int) $figures['earliest_set'].'</b> to <b>'.(int) $figures['latest_set'].'</b>'
                : '';
        @endphp

        @if ($figures['sets'])
            <p class="stat">
                <b>{{ number_format($figures['copies']) }}</b> copies of
                <b>{{ number_format($figures['sets']) }}</b> sets across
                <b>{{ number_format($figures['themes']) }}</b> themes{!! $span !!}.
            </p>
        @endif

        <a class="cta" href="{{ route('filament.admin.auth.login') }}">
            <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <rect x="3.5" y="7" width="9" height="6.5" rx="1.4" stroke="currentColor" stroke-width="1.5" />
                <path d="M5.5 7V5.2a2.5 2.5 0 0 1 5 0V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
            Sign In
        </a>

        @if ($figures['sets'])
            <dl class="figures">
                <div class="figure">
                    <dd>{{ number_format($figures['sets']) }}</dd>
                    <dt>Sets</dt>
                </div>
                <div class="figure">
                    <dd>{{ number_format($figures['pieces']) }}</dd>
                    <dt>Pieces</dt>
                </div>
                <div class="figure">
                    <dd>{{ number_format($figures['minifigures']) }}</dd>
                    <dt>Minifigures</dt>
                </div>
            </dl>
        @endif

        <p class="colophon">
            Private collection{{ $figures['collecting_since'] ? ' · Since '.(int) $figures['collecting_since'] : '' }}
        </p>
    </main>

    <p class="legal">
        LEGO® is a trademark of the LEGO Group, which does not sponsor, authorise or endorse this site.
    </p>
</body>

</html>
