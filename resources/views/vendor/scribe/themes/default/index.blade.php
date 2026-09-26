@php
    use Knuckles\Scribe\Tools\WritingUtils as u;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{!! $metadata['title'] !!}</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{!! $assetPathPrefix !!}css/theme-default.style.css" media="screen">
    <link rel="stylesheet" href="{!! $assetPathPrefix !!}css/theme-default.print.css" media="print">

    <style>
        :root {
            --ukapi-blue: #1248e8;
            --ukapi-blue-dark: #0f3dc4;
            --ukapi-ink: #0f172a;
            --ukapi-muted: #64748b;
            --ukapi-line: #e2e8f0;
            --ukapi-panel: #f8fafc;
            --ukapi-code: #0d1529;
        }

        html,
        body,
        .content h1,
        .content h2,
        .content h3,
        .content h4,
        .content h5,
        .content h6 {
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        }

        body,
        html {
            background: var(--ukapi-panel);
            color: var(--ukapi-ink);
            font-size: 15px;
        }

        .content code,
        .content pre,
        .ukapi-docs-brand__eyebrow {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .tocify-wrapper {
            width: 264px;
            background: linear-gradient(180deg, #101a33 0%, #0d1529 100%);
            box-shadow: 1px 0 0 rgba(148, 163, 184, .16);
            font-weight: 500;
        }

        .ukapi-docs-brand {
            display: block;
            margin: 18px 16px 14px;
            padding: 12px;
            border: 1px solid rgba(191, 219, 254, .16);
            border-radius: 12px;
            background: rgba(30, 58, 138, .17);
            color: #fff;
            text-decoration: none;
        }

        .ukapi-docs-brand:hover {
            border-color: rgba(191, 219, 254, .35);
            background: rgba(30, 58, 138, .3);
        }

        .ukapi-docs-brand__name {
            display: block;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -.03em;
        }

        .ukapi-docs-brand__eyebrow {
            display: block;
            margin-top: 3px;
            color: #bfdbfe;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tocify-wrapper>.search {
            margin: 0 16px 12px;
        }

        .tocify-wrapper>.search:before {
            top: 10px;
            left: 11px;
            color: #94a3b8;
        }

        .tocify-wrapper>.search input {
            width: 100%;
            margin: 0;
            padding: 9px 10px 9px 30px;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
            background: rgba(15, 23, 42, .78);
            color: #fff;
            font-size: 12px;
        }

        .tocify-wrapper>.search input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
        }

        .tocify-wrapper li,
        .tocify-wrapper ul {
            line-height: 30px;
        }

        .tocify-wrapper .tocify-item>a {
            padding-right: 16px;
            padding-left: 22px;
            color: #cbd5e1;
        }

        .tocify-wrapper .tocify-item.level-2>a {
            padding-left: 32px;
            color: #94a3b8;
            font-size: 12px;
        }

        .tocify-wrapper .tocify-item.level-3>a {
            padding-left: 43px;
            font-size: 12px;
        }

        .tocify-wrapper .tocify-item>a:hover,
        .tocify-wrapper .tocify-focus {
            background: rgba(59, 130, 246, .17);
            box-shadow: inset 2px 0 0 var(--ukapi-blue);
            color: #fff;
        }

        .tocify-wrapper .tocify-subheader {
            background: transparent;
            box-shadow: none;
        }

        .tocify-wrapper .toc-footer {
            margin: 18px 16px 0;
            padding: 14px 0 0;
            border-top: 1px solid rgba(148, 163, 184, .17);
        }

        .tocify-wrapper .toc-footer a,
        .tocify-wrapper .toc-footer li {
            color: #94a3b8;
            font-size: 11px;
        }

        .tocify-wrapper .toc-footer a:hover {
            color: #dbeafe;
            text-decoration: none;
        }

        .page-wrapper {
            min-height: 100vh;
            margin-left: 264px;
            background: var(--ukapi-panel);
        }

        .page-wrapper .dark-box {
            width: 48%;
            background: var(--ukapi-code);
        }

        .content>aside,
        .content>details,
        .content>dl,
        .content>h1,
        .content>h2,
        .content>h3,
        .content>h4,
        .content>h5,
        .content>h6,
        .content>ol,
        .content>p,
        .content>table,
        .content>ul,
        .content>div,
        .content>form>aside,
        .content>form>details,
        .content>form>h1,
        .content>form>h2,
        .content>form>h3,
        .content>form>h4,
        .content>form>h5,
        .content>form>h6,
        .content>form>p,
        .content>form>table,
        .content>form>ul,
        .content>form>div {
            margin-right: 48%;
            padding-right: 40px;
            padding-left: 40px;
            text-shadow: none;
        }

        .content h1 {
            margin-top: 50px;
            margin-bottom: 20px;
            padding-top: 0;
            padding-bottom: 15px;
            border-top: 0;
            border-bottom: 1px solid var(--ukapi-line);
            background: transparent;
            color: var(--ukapi-ink);
            font-size: 28px;
            letter-spacing: -.04em;
            line-height: 1.15;
        }

        .content div:first-child+h1,
        .content h1:first-child {
            margin-top: 42px;
        }

        .content h2 {
            margin-top: 38px;
            padding-top: 26px;
            padding-bottom: 0;
            border-top: 1px solid var(--ukapi-line);
            background: transparent;
            color: #172554;
            font-size: 19px;
            letter-spacing: -.025em;
            line-height: 1.25;
        }

        .content h3 {
            color: #334155;
            font-size: 14px;
            letter-spacing: -.01em;
        }

        .content p,
        .content li,
        .content dd,
        .content dt {
            color: #475569;
            line-height: 1.75;
        }

        .content a {
            color: var(--ukapi-blue);
            font-weight: 600;
        }

        .content a:hover {
            color: var(--ukapi-blue-dark);
        }

        .content :not(pre)>code {
            padding: 2px 5px;
            border: 1px solid #dbeafe;
            border-radius: 4px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: .84em;
        }

        .content aside {
            margin-top: 24px;
            margin-bottom: 24px;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .content aside.notice:before {
            color: var(--ukapi-blue);
        }

        .content table {
            margin-top: 8px;
            margin-bottom: 22px;
            overflow: hidden;
            border: 1px solid var(--ukapi-line);
            border-radius: 9px;
            background: #fff;
        }

        .content table th {
            padding: 9px 12px;
            border-bottom-color: var(--ukapi-line);
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
        }

        .content table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .content table tr:nth-child(odd)>td,
        .content table tr:nth-child(even)>td {
            background: #fff;
        }

        .content blockquote,
        .content pre,
        .content .annotation {
            width: 48%;
            padding-right: 34px;
            padding-left: 34px;
            background: var(--ukapi-code);
            color: #dbeafe;
            text-shadow: none;
        }

        .content blockquote {
            padding-top: 19px;
            padding-bottom: 7px;
            color: #93c5fd;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .content blockquote>p {
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #93c5fd;
            padding: 0;
        }

        .content pre {
            padding-top: 8px;
            padding-bottom: 28px;
        }

        .content pre code,
        .content .annotation code {
            color: #dbeafe;
            font-size: 12px;
            line-height: 1.7;
        }

        .content .fancy-heading-panel {
            width: auto;
            margin: 20px 40px 8px;
            padding: 7px 10px !important;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .content button {
            border-radius: 6px !important;
            font-size: 12px;
            font-weight: 600;
        }

        .content input,
        .content select,
        .content textarea {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            box-shadow: none !important;
        }

        .badge {
            border-radius: 5px;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 10px;
            letter-spacing: .02em;
        }

        .page-wrapper .lang-selector,
        .lang-selector {
            border-bottom-color: var(--ukapi-code);
            background: rgba(13, 21, 41, .96);
        }

        .lang-selector button.active,
        .lang-selector button:hover,
        .lang-selector button:focus {
            background: rgba(30, 58, 138, .65);
        }

        @media (max-width: 930px) {
            .tocify-wrapper {
                left: -264px;
            }

            .page-wrapper {
                margin-left: 0;
            }

            #nav-button.open {
                left: 264px;
            }
        }

        @media (max-width: 700px) {
            .content>aside,
            .content>details,
            .content>dl,
            .content>h1,
            .content>h2,
            .content>h3,
            .content>h4,
            .content>h5,
            .content>h6,
            .content>ol,
            .content>p,
            .content>table,
            .content>ul,
            .content>div,
            .content>form>aside,
            .content>form>details,
            .content>form>h1,
            .content>form>h2,
            .content>form>h3,
            .content>form>h4,
            .content>form>h5,
            .content>form>h6,
            .content>form>p,
            .content>form>table,
            .content>form>ul,
            .content>form>div {
                padding-right: 22px;
                padding-left: 22px;
            }

            .content blockquote,
            .content pre,
            .content .annotation {
                padding-right: 22px;
                padding-left: 22px;
            }

            .content .fancy-heading-panel {
                margin-right: 22px;
                margin-left: 22px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

@if(isset($metadata['example_languages']))
    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
        @foreach($metadata['example_languages'] as $lang)
            body .content .{{ $lang }}-example code { display: none; }
        @endforeach
    </style>
@endif

    <script src="{{ u::getVersionedAsset($assetPathPrefix.'js/theme-default.js') }}"></script>

</head>

<body data-languages="{{ json_encode($metadata['example_languages'] ?? []) }}">

@include("scribe::themes.default.sidebar")

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        {!! $intro !!}

        {!! $auth !!}

        @include("scribe::themes.default.groups")

        {!! $append !!}
    </div>
    <div class="dark-box">
        @if(isset($metadata['example_languages']))
            <div class="lang-selector">
                @foreach($metadata['example_languages'] as $name => $lang)
                    @php if (is_numeric($name)) $name = $lang; @endphp
                    <button type="button" class="lang-button" data-language-name="{{$lang}}">{{$name}}</button>
                @endforeach
            </div>
        @endif
    </div>
</div>
</body>
</html>
