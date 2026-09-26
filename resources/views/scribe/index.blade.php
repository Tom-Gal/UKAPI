<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>UKAPI.io API reference</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

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

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
                    body .content .php-example code { display: none; }
            </style>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;,&quot;php&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    <a href="/" class="ukapi-docs-brand" aria-label="UKAPI.io home">
        <span class="ukapi-docs-brand__name">UKAPI.io</span>
        <span class="ukapi-docs-brand__eyebrow">API reference · v1</span>
    </a>

            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                            <button type="button" class="lang-button" data-language-name="php">php</button>
                    </div>

    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-build-with-ukapiio" class="tocify-header">
                <li class="tocify-item level-1" data-unique="build-with-ukapiio">
                    <a href="#build-with-ukapiio">Build with UKAPI.io</a>
                </li>
                                    <ul id="tocify-subheader-build-with-ukapiio" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="quick-start">
                                <a href="#quick-start">Quick start</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="api-conventions">
                                <a href="#api-conventions">API conventions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="errors-limits-and-pagination">
                                <a href="#errors-limits-and-pagination">Errors, limits and pagination</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-postcodes-location" class="tocify-header">
                <li class="tocify-item level-1" data-unique="postcodes-location">
                    <a href="#postcodes-location">Postcodes & location</a>
                </li>
                                    <ul id="tocify-subheader-postcodes-location" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="postcodes-location-GETv1-postcodes--postcode-">
                                <a href="#postcodes-location-GETv1-postcodes--postcode-">Look up a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="postcodes-location-GETv1-postcodes--postcode--validate">
                                <a href="#postcodes-location-GETv1-postcodes--postcode--validate">Validate a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="postcodes-location-GETv1-postcodes--postcode--nearby">
                                <a href="#postcodes-location-GETv1-postcodes--postcode--nearby">Find nearby postcodes.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="postcodes-location-GETv1-coordinates--latitude---longitude--postcode">
                                <a href="#postcodes-location-GETv1-coordinates--latitude---longitude--postcode">Reverse geocode coordinates.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-companies-sic" class="tocify-header">
                <li class="tocify-item level-1" data-unique="companies-sic">
                    <a href="#companies-sic">Companies & SIC</a>
                </li>
                                    <ul id="tocify-subheader-companies-sic" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="companies-sic-GETv1-companies-search">
                                <a href="#companies-sic-GETv1-companies-search">Search companies.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="companies-sic-GETv1-companies--companyNumber-">
                                <a href="#companies-sic-GETv1-companies--companyNumber-">Look up a company.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="companies-sic-GETv1-companies--companyNumber--officers">
                                <a href="#companies-sic-GETv1-companies--companyNumber--officers">List company officers.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="companies-sic-GETv1-companies--companyNumber--filings">
                                <a href="#companies-sic-GETv1-companies--companyNumber--filings">List company filings.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="companies-sic-GETv1-sic-search">
                                <a href="#companies-sic-GETv1-sic-search">Search SIC codes.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="companies-sic-GETv1-sic--code-">
                                <a href="#companies-sic-GETv1-sic--code-">Look up a SIC code.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-crime-data" class="tocify-header">
                <li class="tocify-item level-1" data-unique="crime-data">
                    <a href="#crime-data">Crime data</a>
                </li>
                                    <ul id="tocify-subheader-crime-data" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="crime-data-GETv1-crime-nearby">
                                <a href="#crime-data-GETv1-crime-nearby">Find crime near a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="crime-data-GETv1-crime-summary">
                                <a href="#crime-data-GETv1-crime-summary">Summarise crime near a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="crime-data-GETv1-crime-categories">
                                <a href="#crime-data-GETv1-crime-categories">List crime categories.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-flood-monitoring" class="tocify-header">
                <li class="tocify-item level-1" data-unique="flood-monitoring">
                    <a href="#flood-monitoring">Flood monitoring</a>
                </li>
                                    <ul id="tocify-subheader-flood-monitoring" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="flood-monitoring-GETv1-flood-warnings">
                                <a href="#flood-monitoring-GETv1-flood-warnings">List current flood warnings.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="flood-monitoring-GETv1-flood-nearby">
                                <a href="#flood-monitoring-GETv1-flood-nearby">Find flood warnings near a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="flood-monitoring-GETv1-flood-stations-nearby">
                                <a href="#flood-monitoring-GETv1-flood-stations-nearby">Find flood stations near a postcode.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="flood-monitoring-GETv1-flood-stations--station--readings">
                                <a href="#flood-monitoring-GETv1-flood-stations--station--readings">List flood station readings.</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-vat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="vat">
                    <a href="#vat">VAT</a>
                </li>
                                    <ul id="tocify-subheader-vat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="vat-GETv1-vat-calculate">
                                <a href="#vat-GETv1-vat-calculate">Calculate VAT.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="vat-GETv1-vat-remove">
                                <a href="#vat-GETv1-vat-remove">Remove VAT.</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="https://github.com/knuckleswtf/scribe">Built with Scribe</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: September 26, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>A focused, interactive reference for every live UKAPI.io v1 endpoint.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<h1 id="build-with-ukapiio">Build with UKAPI.io</h1>
<p>UK data through one dependable v1 contract. Start with a test key, choose an endpoint, and use the request and response examples without leaving the reference.</p>
<aside class="notice">All public API requests use a customer API key. Browser sessions are never used to authenticate calls to <code>/v1</code>.</aside>
<h2 id="quick-start">Quick start</h2>
<ol>
<li>Create an account and copy a <strong>test</strong> key from your dashboard. Keys are shown once, so keep it in a secure secret store.</li>
<li>Send it as a Bearer token with every request.</li>
<li>Start with a postcode lookup, then explore the endpoint groups below.</li>
</ol>
<pre><code class="language-bash">curl "{{ config('app.url') }}/v1/postcodes/BL2%206XX" \
  -H "Authorization: Bearer {YOUR_API_KEY}" \
  -H "Accept: application/json"</code></pre>
<h2 id="api-conventions">API conventions</h2>
<p>Successful responses use a consistent <code>{ data, meta }</code> envelope. <code>meta.request_id</code> is present on every response; keep it when contacting support. Provider-backed responses also identify their source, coverage and caching state. Use the versioned <code>/v1</code> paths in production clients.</p>
<h2 id="errors-limits-and-pagination">Errors, limits and pagination</h2>
<p>Failures use <code>{ error: { code, message, status, request_id } }</code>. Handle errors by <code>code</code>, not human-readable text. <code>429</code> can indicate either a short burst limit or a monthly quota; honour <code>Retry-After</code> and the rate-limit headers when supplied. Paginated list endpoints return <code>meta.pagination</code> with <code>page</code>, <code>per_page</code> and <code>total</code>.</p>
<p>For licensing, data provenance and geographic caveats, see the <a href="/data-sources">data sources register</a>. Flood data is informational only and is <strong>not</strong> an emergency alert service.</p>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {YOUR_API_KEY}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Create a test or live key in your <a href="/api-keys">dashboard</a>, then send <code>Authorization: Bearer {YOUR_API_KEY}</code>. Keys are shown only when created and should never be placed in client-side code.</p>

        <h1 id="postcodes-location">Postcodes & location</h1>

    <p>Canonical UK postcode, nearby-postcode and coordinate lookup data from Postcodes.io.</p>

                                <h2 id="postcodes-location-GETv1-postcodes--postcode-">Look up a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns a normalised UK postcode and selected geographic references.</p>

<span id="example-requests-GETv1-postcodes--postcode-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/postcodes/BL2 6XX" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/postcodes/BL2 6XX"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/postcodes/BL2 6XX';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-postcodes--postcode-">
            <blockquote>
            <p>Example response (200, Postcode found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;country&quot;: &quot;England&quot;,
        &quot;region&quot;: &quot;North West&quot;,
        &quot;latitude&quot;: 53.5922,
        &quot;longitude&quot;: -2.4117,
        &quot;local_authority&quot;: {
            &quot;name&quot;: &quot;Bolton&quot;,
            &quot;code&quot;: &quot;E08000001&quot;
        }
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;postcodes_io&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, Burst limit or monthly quota reached):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;rate_limit_exceeded&quot;,
        &quot;message&quot;: &quot;Too many requests. Please retry shortly.&quot;,
        &quot;status&quot;: 429,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/postcodes/{postcode}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>UK postcode to look up. Spacing and case are normalised. Example: <code>BL2 6XX</code></p>
            </div>
                    </section>

                    <h2 id="postcodes-location-GETv1-postcodes--postcode--validate">Validate a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Checks whether a normalised UK postcode has a provider record.</p>

<span id="example-requests-GETv1-postcodes--postcode--validate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/postcodes/BL2 6XX/validate" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/postcodes/BL2 6XX/validate"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/postcodes/BL2 6XX/validate';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-postcodes--postcode--validate">
            <blockquote>
            <p>Example response (200, Postcode validation result):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;valid&quot;: true
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: true,
        &quot;source&quot;: &quot;postcodes_io&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, Burst limit or monthly quota reached):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;rate_limit_exceeded&quot;,
        &quot;message&quot;: &quot;Too many requests. Please retry shortly.&quot;,
        &quot;status&quot;: 429,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/postcodes/{postcode}/validate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>UK postcode to validate. Spacing and case are normalised. Example: <code>BL2 6XX</code></p>
            </div>
                    </section>

                    <h2 id="postcodes-location-GETv1-postcodes--postcode--nearby">Find nearby postcodes.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Results are ordered by distance in metres.</p>

<span id="example-requests-GETv1-postcodes--postcode--nearby">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/postcodes/BL2 6XX/nearby?limit=5&amp;radius=500" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/postcodes/BL2 6XX/nearby"
);

const params = {
    "limit": "5",
    "radius": "500",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/postcodes/BL2 6XX/nearby';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'limit' =&gt; '5',
            'radius' =&gt; '500',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-postcodes--postcode--nearby">
            <blockquote>
            <p>Example response (200, Nearby postcodes):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;results&quot;: [
            {
                &quot;postcode&quot;: &quot;BL2 6XY&quot;,
                &quot;distance_metres&quot;: 152.4,
                &quot;latitude&quot;: 53.5931,
                &quot;longitude&quot;: -2.4108
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;postcodes_io&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, Burst limit or monthly quota reached):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;rate_limit_exceeded&quot;,
        &quot;message&quot;: &quot;Too many requests. Please retry shortly.&quot;,
        &quot;status&quot;: 429,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/postcodes/{postcode}/nearby</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>UK postcode used as the search origin. Example: <code>BL2 6XX</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Number of results to return, from 1 to 100. Defaults to 10. Must be between 1 and 100. Example: <code>5</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>radius</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Search radius in metres, from 1 to 2,000. Defaults to 100. Must be between 1 and 2000. Example: <code>500</code></p>
            </div>
                </section>

                    <h2 id="postcodes-location-GETv1-coordinates--latitude---longitude--postcode">Reverse geocode coordinates.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns the nearest UK postcode for WGS84 coordinates.</p>

<span id="example-requests-GETv1-coordinates--latitude---longitude--postcode">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/coordinates/53.5922/-2.4117/postcode" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/coordinates/53.5922/-2.4117/postcode"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/coordinates/53.5922/-2.4117/postcode';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-coordinates--latitude---longitude--postcode">
            <blockquote>
            <p>Example response (200, Nearest postcode):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;country&quot;: &quot;England&quot;,
        &quot;latitude&quot;: 53.5922,
        &quot;longitude&quot;: -2.4117
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;postcodes_io&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (429, Burst limit or monthly quota reached):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;rate_limit_exceeded&quot;,
        &quot;message&quot;: &quot;Too many requests. Please retry shortly.&quot;,
        &quot;status&quot;: 429,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/coordinates/{latitude}/{longitude}/postcode</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>latitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>WGS84 latitude from -90 to 90. Example: <code>53.5922</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>longitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>WGS84 longitude from -180 to 180. Example: <code>-2.4117</code></p>
            </div>
                    </section>

                <h1 id="companies-sic">Companies & SIC</h1>

    <p>Simplified Companies House profiles, officers and filing metadata with predictable pagination.</p>

                                <h2 id="companies-sic-GETv1-companies-search">Search companies.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Searches Companies House company records by name.</p>

<span id="example-requests-GETv1-companies-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/companies/search?q=example+technology&amp;page=1&amp;per_page=25" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/companies/search"
);

const params = {
    "q": "example technology",
    "page": "1",
    "per_page": "25",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/companies/search';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'q' =&gt; 'example technology',
            'page' =&gt; '1',
            'per_page' =&gt; '25',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-companies-search">
            <blockquote>
            <p>Example response (200, Paginated company search results):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;company_number&quot;: &quot;SC012345&quot;,
            &quot;name&quot;: &quot;Example Technology Ltd&quot;,
            &quot;status&quot;: &quot;active&quot;,
            &quot;type&quot;: &quot;ltd&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;stale&quot;: false,
        &quot;source&quot;: &quot;companies_house&quot;,
        &quot;coverage&quot;: &quot;UK&quot;,
        &quot;pagination&quot;: {
            &quot;page&quot;: 1,
            &quot;per_page&quot;: 25,
            &quot;total&quot;: 42
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Companies House is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/companies/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Company name search text, between 2 and 200 characters. Must be at least 2 characters. Must not be greater than 200 characters. Example: <code>example technology</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>One-based result page. Defaults to 1. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Results to return per page, from 1 to 100. Defaults to 25. Must be between 1 and 100. Example: <code>25</code></p>
            </div>
                </section>

                    <h2 id="companies-sic-GETv1-companies--companyNumber-">Look up a company.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns a simplified Companies House company profile.</p>

<span id="example-requests-GETv1-companies--companyNumber-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/companies/SC012345" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/companies/SC012345"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/companies/SC012345';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-companies--companyNumber-">
            <blockquote>
            <p>Example response (200, Company profile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;company_number&quot;: &quot;SC012345&quot;,
        &quot;name&quot;: &quot;Example Technology Ltd&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;type&quot;: &quot;ltd&quot;,
        &quot;jurisdiction&quot;: &quot;scotland&quot;,
        &quot;incorporated_on&quot;: &quot;2019-04-12&quot;,
        &quot;sic_codes&quot;: [
            &quot;62012&quot;,
            &quot;63110&quot;
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;stale&quot;: false,
        &quot;source&quot;: &quot;companies_house&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Companies House is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/companies/{companyNumber}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>companyNumber</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Companies House company number. Whitespace is removed and letters are uppercased. Example: <code>SC012345</code></p>
            </div>
                    </section>

                    <h2 id="companies-sic-GETv1-companies--companyNumber--officers">List company officers.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns a company's officers without date-of-birth data.</p>

<span id="example-requests-GETv1-companies--companyNumber--officers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/companies/SC012345/officers?page=1&amp;per_page=25" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/companies/SC012345/officers"
);

const params = {
    "page": "1",
    "per_page": "25",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/companies/SC012345/officers';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '25',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-companies--companyNumber--officers">
            <blockquote>
            <p>Example response (200, Paginated company officers):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;name&quot;: &quot;Alex Example&quot;,
            &quot;role&quot;: &quot;director&quot;,
            &quot;appointed_on&quot;: &quot;2020-01-15&quot;,
            &quot;nationality&quot;: &quot;British&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;stale&quot;: false,
        &quot;source&quot;: &quot;companies_house&quot;,
        &quot;coverage&quot;: &quot;UK&quot;,
        &quot;pagination&quot;: {
            &quot;page&quot;: 1,
            &quot;per_page&quot;: 25,
            &quot;total&quot;: 2
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Companies House is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/companies/{companyNumber}/officers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>companyNumber</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Companies House company number whose officers are requested. Example: <code>SC012345</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>One-based result page. Defaults to 1. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Results to return per page, from 1 to 100. Defaults to 25. Must be between 1 and 100. Example: <code>25</code></p>
            </div>
                </section>

                    <h2 id="companies-sic-GETv1-companies--companyNumber--filings">List company filings.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns filing metadata; documents, links and contents are intentionally not proxied.</p>

<span id="example-requests-GETv1-companies--companyNumber--filings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/companies/SC012345/filings?page=1&amp;per_page=25" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/companies/SC012345/filings"
);

const params = {
    "page": "1",
    "per_page": "25",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/companies/SC012345/filings';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '25',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-companies--companyNumber--filings">
            <blockquote>
            <p>Example response (200, Paginated company filing metadata):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;transaction_id&quot;: &quot;MzAwMDAwMDAwMGFkaXF6a2N4&quot;,
            &quot;category&quot;: &quot;accounts&quot;,
            &quot;type&quot;: &quot;AA&quot;,
            &quot;filed_on&quot;: &quot;2025-03-31&quot;,
            &quot;description&quot;: &quot;accounts-with-accounts-type-full&quot;,
            &quot;pages&quot;: 12
        }
    ],
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;stale&quot;: false,
        &quot;source&quot;: &quot;companies_house&quot;,
        &quot;coverage&quot;: &quot;UK&quot;,
        &quot;pagination&quot;: {
            &quot;page&quot;: 1,
            &quot;per_page&quot;: 25,
            &quot;total&quot;: 42
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Companies House is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/companies/{companyNumber}/filings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>companyNumber</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Companies House company number whose filing history is requested. Example: <code>SC012345</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>One-based result page. Defaults to 1. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Results to return per page, from 1 to 100. Defaults to 25. Must be between 1 and 100. Example: <code>25</code></p>
            </div>
                </section>

                    <h2 id="companies-sic-GETv1-sic-search">Search SIC codes.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Searches the SIC reference by code or description.</p>

<span id="example-requests-GETv1-sic-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/sic/search?q=software&amp;page=1&amp;per_page=25" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/sic/search"
);

const params = {
    "q": "software",
    "page": "1",
    "per_page": "25",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/sic/search';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'q' =&gt; 'software',
            'page' =&gt; '1',
            'per_page' =&gt; '25',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-sic-search">
            <blockquote>
            <p>Example response (200, Paginated SIC results):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;code&quot;: &quot;62012&quot;,
            &quot;description&quot;: &quot;Business and domestic software development&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;companies_house_sic_2007&quot;,
        &quot;source_updated_at&quot;: &quot;2026-09-24T12:00:00+00:00&quot;,
        &quot;coverage&quot;: &quot;UK&quot;,
        &quot;reference_version&quot;: &quot;companies_house_condensed_sic_2007&quot;,
        &quot;pagination&quot;: {
            &quot;page&quot;: 1,
            &quot;per_page&quot;: 25,
            &quot;total&quot;: 18
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, The SIC snapshot is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;sic_reference_unavailable&quot;,
        &quot;message&quot;: &quot;The SIC reference snapshot is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/sic/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>SIC code or description search text, between 2 and 200 characters. Must be at least 2 characters. Must not be greater than 200 characters. Example: <code>software</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>One-based result page. Defaults to 1. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Results to return per page, from 1 to 100. Defaults to 25. Must be between 1 and 100. Example: <code>25</code></p>
            </div>
                </section>

                    <h2 id="companies-sic-GETv1-sic--code-">Look up a SIC code.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieves a five-digit Companies House condensed SIC 2007 code.</p>

<span id="example-requests-GETv1-sic--code-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/sic/62012" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/sic/62012"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/sic/62012';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-sic--code-">
            <blockquote>
            <p>Example response (200, SIC record):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;code&quot;: &quot;62012&quot;,
        &quot;description&quot;: &quot;Business and domestic software development&quot;
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;companies_house_sic_2007&quot;,
        &quot;source_updated_at&quot;: &quot;2026-09-24T12:00:00+00:00&quot;,
        &quot;coverage&quot;: &quot;UK&quot;,
        &quot;reference_version&quot;: &quot;companies_house_condensed_sic_2007&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, The SIC snapshot is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;sic_reference_unavailable&quot;,
        &quot;message&quot;: &quot;The SIC reference snapshot is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/sic/{code}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Five-digit Companies House condensed SIC 2007 code. Whitespace is removed. Example: <code>62012</code></p>
            </div>
                    </section>

                <h1 id="crime-data">Crime data</h1>

    <p>Approximate, anonymised street-level crime data from Police.uk. Coverage is England, Wales and Northern Ireland; Scotland includes British Transport Police data only.</p>

                                <h2 id="crime-data-GETv1-crime-nearby">Find crime near a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Returns approximate street-level crime records near a postcode.</p>

<span id="example-requests-GETv1-crime-nearby">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/crime/nearby?postcode=BL2+6XX&amp;month=2026-07" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/crime/nearby"
);

const params = {
    "postcode": "BL2 6XX",
    "month": "2026-07",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/crime/nearby';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'postcode' =&gt; 'BL2 6XX',
            'month' =&gt; '2026-07',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-crime-nearby">
            <blockquote>
            <p>Example response (200, Approximate crime records):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;crimes&quot;: [
            {
                &quot;category&quot;: &quot;anti-social-behaviour&quot;,
                &quot;persistent_id&quot;: null,
                &quot;month&quot;: &quot;2026-07&quot;,
                &quot;location&quot;: {
                    &quot;latitude&quot;: 53.592132,
                    &quot;longitude&quot;: -2.411624,
                    &quot;street&quot;: {
                        &quot;id&quot;: 123456,
                        &quot;name&quot;: &quot;On or near Example Street&quot;
                    },
                    &quot;approximate&quot;: true
                },
                &quot;outcome&quot;: {
                    &quot;category&quot;: null,
                    &quot;month&quot;: null
                }
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;police_uk&quot;,
        &quot;coverage&quot;: &quot;England, Wales and Northern Ireland; Scotland has British Transport Police data only.&quot;,
        &quot;requested_month&quot;: &quot;2026-07&quot;,
        &quot;data_is_approximate&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Police.uk is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/crime/nearby</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A UK postcode. It is normalised and resolved to coordinates before the Police.uk request. Must not be greater than 16 characters. Example: <code>BL2 6XX</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>month</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A non-future month in YYYY-MM format. Omit it to use the latest provider data. Must be a valid date in the format <code>Y-m</code>. Example: <code>2026-07</code></p>
            </div>
                </section>

                    <h2 id="crime-data-GETv1-crime-summary">Summarise crime near a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Aggregates approximate street-level crime records by category.</p>

<span id="example-requests-GETv1-crime-summary">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/crime/summary?postcode=BL2+6XX&amp;month=2026-07" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/crime/summary"
);

const params = {
    "postcode": "BL2 6XX",
    "month": "2026-07",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/crime/summary';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'postcode' =&gt; 'BL2 6XX',
            'month' =&gt; '2026-07',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-crime-summary">
            <blockquote>
            <p>Example response (200, Crime category summary):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;month&quot;: &quot;2026-07&quot;,
        &quot;total&quot;: 2,
        &quot;by_category&quot;: [
            {
                &quot;category&quot;: &quot;anti-social-behaviour&quot;,
                &quot;count&quot;: 1
            },
            {
                &quot;category&quot;: &quot;violent-crime&quot;,
                &quot;count&quot;: 1
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;police_uk&quot;,
        &quot;coverage&quot;: &quot;England, Wales and Northern Ireland; Scotland has British Transport Police data only.&quot;,
        &quot;requested_month&quot;: &quot;2026-07&quot;,
        &quot;data_is_approximate&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Police.uk is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/crime/summary</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A UK postcode. It is normalised and resolved to coordinates before the Police.uk request. Must not be greater than 16 characters. Example: <code>BL2 6XX</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>month</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A non-future month in YYYY-MM format. Omit it to use the latest provider data. Must be a valid date in the format <code>Y-m</code>. Example: <code>2026-07</code></p>
            </div>
                </section>

                    <h2 id="crime-data-GETv1-crime-categories">List crime categories.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Lists Police.uk crime categories for an optional month.</p>

<span id="example-requests-GETv1-crime-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/crime/categories?month=2026-07" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/crime/categories"
);

const params = {
    "month": "2026-07",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/crime/categories';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'month' =&gt; '2026-07',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-crime-categories">
            <blockquote>
            <p>Example response (200, Crime categories):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;categories&quot;: [
            {
                &quot;code&quot;: &quot;all-crime&quot;,
                &quot;name&quot;: &quot;All crime and anti-social behaviour&quot;
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;police_uk&quot;,
        &quot;coverage&quot;: &quot;England, Wales and Northern Ireland; Scotland has British Transport Police data only.&quot;,
        &quot;requested_month&quot;: &quot;2026-07&quot;,
        &quot;data_is_approximate&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Police.uk is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/crime/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>month</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A non-future month in YYYY-MM format. Omit it to use the latest provider categories. Must be a valid date in the format <code>Y-m</code>. Example: <code>2026-07</code></p>
            </div>
                </section>

                <h1 id="flood-monitoring">Flood monitoring</h1>

    <p>Near-real-time England flood warnings, stations and readings from the Environment Agency. This data is not an emergency alert service.</p>

                                <h2 id="flood-monitoring-GETv1-flood-warnings">List current flood warnings.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Lists current England flood warnings and alerts. This endpoint is not an emergency alert service.</p>

<span id="example-requests-GETv1-flood-warnings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/flood/warnings" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/flood/warnings"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/flood/warnings';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-flood-warnings">
            <blockquote>
            <p>Example response (200, Current flood warnings):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;warnings&quot;: [
            {
                &quot;id&quot;: &quot;061WAFSF3A&quot;,
                &quot;severity&quot;: 2,
                &quot;severity_level&quot;: &quot;Flood Warning&quot;,
                &quot;type&quot;: &quot;Flood Warning&quot;,
                &quot;message&quot;: &quot;Flooding is possible in low lying areas.&quot;,
                &quot;raised_at&quot;: &quot;2026-09-25T09:00:00+00:00&quot;,
                &quot;area&quot;: {
                    &quot;code&quot;: &quot;061WAFSF3A&quot;,
                    &quot;county&quot;: &quot;Somerset&quot;,
                    &quot;river_or_sea&quot;: &quot;River Tone&quot;
                }
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;environment_agency_flood_monitoring&quot;,
        &quot;retrieved_at&quot;: &quot;2026-09-25T10:20:00+00:00&quot;,
        &quot;source_updated_at&quot;: &quot;2026-09-25T10:15:00+00:00&quot;,
        &quot;coverage&quot;: &quot;England&quot;,
        &quot;safety_notice&quot;: &quot;This data is not an emergency alert service. Follow official Environment Agency advice and warnings.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Environment Agency data is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/flood/warnings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        </section>

                    <h2 id="flood-monitoring-GETv1-flood-nearby">Find flood warnings near a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Finds current England flood warnings and alerts near a postcode.</p>

<span id="example-requests-GETv1-flood-nearby">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/flood/nearby?postcode=BL2+6XX" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/flood/nearby"
);

const params = {
    "postcode": "BL2 6XX",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/flood/nearby';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'postcode' =&gt; 'BL2 6XX',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-flood-nearby">
            <blockquote>
            <p>Example response (200, Nearby flood warnings):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;nearby_distance_kilometres&quot;: 10,
        &quot;warnings&quot;: []
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;environment_agency_flood_monitoring&quot;,
        &quot;retrieved_at&quot;: &quot;2026-09-25T10:20:00+00:00&quot;,
        &quot;source_updated_at&quot;: &quot;2026-09-25T10:15:00+00:00&quot;,
        &quot;coverage&quot;: &quot;England&quot;,
        &quot;safety_notice&quot;: &quot;This data is not an emergency alert service. Follow official Environment Agency advice and warnings.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Environment Agency data is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/flood/nearby</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A UK postcode. It is normalised and resolved to coordinates before querying the Environment Agency. Must not be greater than 16 characters. Example: <code>BL2 6XX</code></p>
            </div>
                </section>

                    <h2 id="flood-monitoring-GETv1-flood-stations-nearby">Find flood stations near a postcode.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Finds Environment Agency monitoring stations near a postcode.</p>

<span id="example-requests-GETv1-flood-stations-nearby">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/flood/stations/nearby?postcode=BL2+6XX" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/flood/stations/nearby"
);

const params = {
    "postcode": "BL2 6XX",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/flood/stations/nearby';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'postcode' =&gt; 'BL2 6XX',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-flood-stations-nearby">
            <blockquote>
            <p>Example response (200, Nearby flood-monitoring stations):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;postcode&quot;: &quot;BL2 6XX&quot;,
        &quot;nearby_distance_kilometres&quot;: 10,
        &quot;stations&quot;: [
            {
                &quot;id&quot;: &quot;5380TH&quot;,
                &quot;name&quot;: &quot;Walthamstow, Low Hall&quot;,
                &quot;latitude&quot;: 51.574894,
                &quot;longitude&quot;: -0.043637,
                &quot;river_name&quot;: &quot;River Lee&quot;,
                &quot;town&quot;: &quot;Walthamstow&quot;,
                &quot;status&quot;: &quot;statusActive&quot;,
                &quot;measures&quot;: [
                    {
                        &quot;parameter&quot;: &quot;level&quot;,
                        &quot;parameter_name&quot;: &quot;Water Level&quot;,
                        &quot;unit&quot;: &quot;mASD&quot;,
                        &quot;period_seconds&quot;: 900
                    }
                ]
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;environment_agency_flood_monitoring&quot;,
        &quot;retrieved_at&quot;: &quot;2026-09-25T10:20:00+00:00&quot;,
        &quot;source_updated_at&quot;: null,
        &quot;coverage&quot;: &quot;England&quot;,
        &quot;safety_notice&quot;: &quot;This data is not an emergency alert service. Follow official Environment Agency advice and warnings.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Environment Agency data is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/flood/stations/nearby</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>postcode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>A UK postcode. It is normalised and resolved to coordinates before querying the Environment Agency. Must not be greater than 16 characters. Example: <code>BL2 6XX</code></p>
            </div>
                </section>

                    <h2 id="flood-monitoring-GETv1-flood-stations--station--readings">List flood station readings.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Lists a bounded set of recent readings for an Environment Agency station.</p>

<span id="example-requests-GETv1-flood-stations--station--readings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/flood/stations/5380TH/readings" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/flood/stations/5380TH/readings"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/flood/stations/5380TH/readings';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-flood-stations--station--readings">
            <blockquote>
            <p>Example response (200, Recent station readings):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;station_id&quot;: &quot;5380TH&quot;,
        &quot;readings&quot;: [
            {
                &quot;measure_id&quot;: &quot;5380TH-level-stage-i-15_min-mASD&quot;,
                &quot;recorded_at&quot;: &quot;2026-08-27T00:00:00+00:00&quot;,
                &quot;value&quot;: 0.027
            }
        ]
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;environment_agency_flood_monitoring&quot;,
        &quot;retrieved_at&quot;: &quot;2026-09-25T10:20:00+00:00&quot;,
        &quot;source_updated_at&quot;: &quot;2026-08-27T00:00:00+00:00&quot;,
        &quot;coverage&quot;: &quot;England&quot;,
        &quot;safety_notice&quot;: &quot;This data is not an emergency alert service. Follow official Environment Agency advice and warnings.&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Environment Agency data is temporarily unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;upstream_unavailable&quot;,
        &quot;message&quot;: &quot;The upstream data provider is temporarily unavailable. Please retry shortly.&quot;,
        &quot;status&quot;: 503,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/flood/stations/{station}/readings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>station</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Environment Agency station identifier. Letters, numbers, hyphens and underscores are accepted. Example: <code>5380TH</code></p>
            </div>
                    </section>

                <h1 id="vat">VAT</h1>

    <p>Deterministic VAT calculations for GBP amounts. Calculations are provided for convenience and are not tax advice.</p>

                                <h2 id="vat-GETv1-vat-calculate">Calculate VAT.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Calculates the VAT to add to a net GBP amount.</p>

<span id="example-requests-GETv1-vat-calculate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/vat/calculate?amount=100.00&amp;rate=20" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/vat/calculate"
);

const params = {
    "amount": "100.00",
    "rate": "20",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/vat/calculate';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'amount' =&gt; '100.00',
            'rate' =&gt; '20',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-vat-calculate">
            <blockquote>
            <p>Example response (200, VAT calculation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;amount&quot;: &quot;100.00&quot;,
        &quot;rate_percent&quot;: &quot;20.00&quot;,
        &quot;vat_amount&quot;: &quot;20.00&quot;,
        &quot;total_amount&quot;: &quot;120.00&quot;,
        &quot;currency&quot;: &quot;GBP&quot;
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;ukapi&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Invalid amount or rate):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_parameter&quot;,
        &quot;message&quot;: &quot;One or more request parameters are invalid.&quot;,
        &quot;status&quot;: 422,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;details&quot;: {
            &quot;fields&quot;: {
                &quot;amount&quot;: [
                    &quot;The amount format is invalid.&quot;
                ]
            }
        }
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/vat/calculate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Amount in GBP, with up to two decimal places. Use the net amount for calculate and the gross amount for remove. Must match the regex /^(?:0|[1-9]\d{0,11})(?:.\d{1,2})?$/. Example: <code>100.00</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>rate</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>VAT rate as a percentage, from 0 to 100, with up to two decimal places. Must match the regex /^(?:0|[1-9]\d{0,2})(?:.\d{1,2})?$/. Must be between 0 and 100. Example: <code>20</code></p>
            </div>
                </section>

                    <h2 id="vat-GETv1-vat-remove">Remove VAT.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Removes VAT from a gross GBP amount.</p>

<span id="example-requests-GETv1-vat-remove">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/v1/vat/remove?amount=100.00&amp;rate=20" \
    --header "Authorization: Bearer {YOUR_API_KEY}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/v1/vat/remove"
);

const params = {
    "amount": "100.00",
    "rate": "20",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_KEY}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/v1/vat/remove';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_API_KEY}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'amount' =&gt; '100.00',
            'rate' =&gt; '20',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>

</span>

<span id="example-responses-GETv1-vat-remove">
            <blockquote>
            <p>Example response (200, VAT removal calculation):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;gross_amount&quot;: &quot;120.00&quot;,
        &quot;rate_percent&quot;: &quot;20.00&quot;,
        &quot;vat_amount&quot;: &quot;20.00&quot;,
        &quot;net_amount&quot;: &quot;100.00&quot;,
        &quot;currency&quot;: &quot;GBP&quot;
    },
    &quot;meta&quot;: {
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;cached&quot;: false,
        &quot;source&quot;: &quot;ukapi&quot;,
        &quot;coverage&quot;: &quot;UK&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing, invalid or revoked API key):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_api_key&quot;,
        &quot;message&quot;: &quot;The API key is invalid.&quot;,
        &quot;status&quot;: 401,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Invalid amount or rate):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;error&quot;: {
        &quot;code&quot;: &quot;invalid_parameter&quot;,
        &quot;message&quot;: &quot;One or more request parameters are invalid.&quot;,
        &quot;status&quot;: 422,
        &quot;request_id&quot;: &quot;req_01j8d5s1wdm85crh9b739hw4gd&quot;,
        &quot;details&quot;: {
            &quot;fields&quot;: {
                &quot;amount&quot;: [
                    &quot;The amount format is invalid.&quot;
                ]
            }
        }
    }
}</code>
 </pre>
    </span>
<section>
    <h3>
        Request
    </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>v1/vat/remove</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>Bearer {YOUR_API_KEY}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Amount in GBP, with up to two decimal places. Use the net amount for calculate and the gross amount for remove. Must match the regex /^(?:0|[1-9]\d{0,11})(?:.\d{1,2})?$/. Example: <code>100.00</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>rate</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>VAT rate as a percentage, from 0 to 100, with up to two decimal places. Must match the regex /^(?:0|[1-9]\d{0,2})(?:.\d{1,2})?$/. Must be between 0 and 100. Example: <code>20</code></p>
            </div>
                </section>




    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                                        <button type="button" class="lang-button" data-language-name="php">php</button>
                            </div>
            </div>
</div>
</body>
</html>
