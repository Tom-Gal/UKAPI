import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CircleCheck,
    Clock3,
    Play,
    ShieldCheck,
} from 'lucide-react';
import { documentationGuides, endpointFamilies } from '@/lib/ukapi-content';

const guideDescriptions: Record<string, string> = {
    quickstart: 'Go from account to first successful response.',
    authentication: 'Use test and live keys safely in every request.',
    errors: 'Handle stable, machine-readable failures.',
    'rate-limits': 'Understand burst limits and monthly quotas.',
    pagination: 'Work consistently with paged result sets.',
    'sources-and-coverage': 'Know exactly where each result comes from.',
};

export default function Docs() {
    const totalEndpoints = endpointFamilies.reduce(
        (total, family) => total + family.endpoints.length,
        0,
    );

    return (
        <>
            <Head title="Documentation">
                <meta
                    name="description"
                    content="Explore UKAPI.io guides, endpoint references, and a live in-browser API explorer."
                />
            </Head>

            <section className="overflow-hidden border-b border-slate-200 bg-[radial-gradient(circle_at_78%_18%,#dbeafe_0,transparent_26%),radial-gradient(circle_at_92%_62%,#e0e7ff_0,transparent_22%),linear-gradient(180deg,#fff_0%,#f7f9fc_100%)]">
                <div className="mx-auto grid max-w-6xl gap-10 px-5 py-14 lg:grid-cols-[1.1fr_.9fr] lg:px-8 lg:py-20">
                    <div className="max-w-2xl">
                        <div className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/75 px-3 py-1.5 text-xs font-bold tracking-[.14em] text-[#1248e8] uppercase shadow-sm">
                            <BookOpen className="size-3.5" />
                            Developer documentation
                        </div>
                        <h1 className="mt-5 text-4xl font-semibold tracking-[-.055em] text-slate-950 sm:text-5xl lg:text-[3.5rem] lg:leading-[1.04]">
                            Build with UK data, without the guesswork.
                        </h1>
                        <p className="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                            Clear guides, predictable endpoint contracts, and a
                            live request explorer for getting from idea to a
                            working integration quickly.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/try-it"
                                className="inline-flex items-center gap-2 rounded-xl bg-[#1248e8] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#0f3dc4] hover:shadow-md"
                            >
                                <Play className="size-4 fill-current" />
                                Try an API
                            </Link>
                            <a
                                href="#reference"
                                className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-950"
                            >
                                Browse endpoints <ArrowRight className="size-4" />
                            </a>
                        </div>
                    </div>

                    <div className="self-center overflow-hidden rounded-2xl border border-slate-800 bg-[#0d1529] p-1.5 shadow-[0_24px_60px_rgba(15,23,42,.2)]">
                        <div className="rounded-xl bg-[linear-gradient(145deg,rgba(30,58,138,.58),rgba(13,21,41,0)_58%)] p-5 sm:p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex gap-1.5">
                                    <span className="size-2.5 rounded-full bg-rose-300/70" />
                                    <span className="size-2.5 rounded-full bg-amber-300/70" />
                                    <span className="size-2.5 rounded-full bg-emerald-300/70" />
                                </div>
                                <span className="font-mono text-[10px] text-blue-200">LIVE EXPLORER</span>
                            </div>
                            <div className="mt-7 flex items-center gap-2 font-mono text-xs">
                                <span className="rounded bg-emerald-400/15 px-2 py-1 font-bold text-emerald-300">GET</span>
                                <span className="truncate text-blue-100">/v1/postcodes/BL2%206XX</span>
                            </div>
                            <div className="mt-5 border-l-2 border-blue-400/80 pl-4 font-mono text-xs leading-6 text-slate-300">
                                <p><span className="text-violet-300">"postcode"</span>: <span className="text-emerald-200">"BL2 6XX"</span>,</p>
                                <p><span className="text-violet-300">"country"</span>: <span className="text-emerald-200">"England"</span>,</p>
                                <p><span className="text-violet-300">"cached"</span>: <span className="text-amber-200">true</span></p>
                            </div>
                            <div className="mt-7 flex items-center justify-between border-t border-white/10 pt-4">
                                <span className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-200"><CircleCheck className="size-4" /> 200 OK</span>
                                <Link href="/try-it" className="text-xs font-semibold text-blue-200 hover:text-white">Open explorer →</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-5 py-10 lg:px-8 lg:py-14">
                <section aria-label="Documentation highlights" className="grid gap-3 sm:grid-cols-3">
                    <Highlight icon={Play} title="Test as you read" body="Send a request, tweak the input, and see the real JSON response." />
                    <Highlight icon={ShieldCheck} title="One predictable contract" body="Authentication, errors and metadata work the same way across APIs." />
                    <Highlight icon={Clock3} title="Freshness made visible" body="See data source, coverage and cache expectations before you build." />
                </section>

                <div className="mt-14 grid gap-12 lg:grid-cols-[.72fr_1.28fr]">
                    <aside className="lg:sticky lg:top-24 lg:self-start">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-bold tracking-[.16em] text-slate-500 uppercase">Start here</p>
                                <h2 className="mt-2 text-2xl font-semibold tracking-tight">Guides for the essentials</h2>
                            </div>
                            <BookOpen className="size-5 text-[#1248e8]" />
                        </div>
                        <nav className="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="Documentation guides">
                            {documentationGuides.map(([slug, title], index) => (
                                <Link key={slug} href={`/docs/${slug}`} className="group flex items-center gap-3 border-b border-slate-100 px-4 py-4 last:border-0 hover:bg-blue-50/60">
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 font-mono text-[11px] font-bold text-slate-500 group-hover:bg-white group-hover:text-[#1248e8]">0{index + 1}</span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-semibold text-slate-900">{title}</span>
                                        <span className="mt-0.5 block truncate text-xs text-slate-500">{guideDescriptions[slug]}</span>
                                    </span>
                                    <ArrowRight className="size-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-[#1248e8]" />
                                </Link>
                            ))}
                        </nav>
                        <div className="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <p className="text-sm font-semibold text-slate-900">Ready to send a request?</p>
                            <p className="mt-1 text-sm leading-6 text-slate-600">Use your test key in the browser-based explorer. Nothing is stored.</p>
                            <Link href="/try-it" className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]">Open Try it out <ArrowRight className="size-4" /></Link>
                        </div>
                    </aside>

                    <section id="reference" className="scroll-mt-28">
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                            <div>
                                <p className="text-xs font-bold tracking-[.16em] text-slate-500 uppercase">Reference</p>
                                <h2 className="mt-2 text-2xl font-semibold tracking-tight">{totalEndpoints} live endpoints, ready to explore</h2>
                            </div>
                            <Link href="/api-catalogue" className="inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]">Full API catalogue <ArrowRight className="size-4" /></Link>
                        </div>
                        <div className="mt-5 grid gap-4">
                            {endpointFamilies.map((family) => (
                                <article key={family.slug} className="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-blue-200 hover:shadow-[0_8px_24px_rgba(15,23,42,.06)]">
                                    <div className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-start">
                                        <div>
                                            <div className="flex items-center gap-3">
                                                <h3 className="font-semibold text-slate-950">{family.name}</h3>
                                                <span className="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-500">{family.endpoints.length} {family.endpoints.length === 1 ? 'endpoint' : 'endpoints'}</span>
                                            </div>
                                            <p className="mt-2 text-sm leading-6 text-slate-600">{family.summary}</p>
                                        </div>
                                        <Link href={`/docs/${family.slug}`} className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]">Reference <ArrowRight className="size-4" /></Link>
                                    </div>
                                    <div className="border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                                        <div className="grid gap-2">
                                            {family.endpoints.slice(0, 3).map((endpoint, index) => (
                                                <div key={`${endpoint.path}-${index}`} className="flex min-w-0 items-center gap-2 font-mono text-xs text-slate-600">
                                                    <span className="rounded bg-emerald-100 px-1.5 py-0.5 font-bold text-emerald-700">GET</span>
                                                    <Link href={`/docs/api/${family.slug}/${index + 1}`} className="truncate hover:text-[#1248e8]">{endpoint.path}</Link>
                                                    <Link href={`/try-it?endpoint=${family.slug}-${index + 1}`} className="ml-auto hidden shrink-0 font-sans text-xs font-semibold text-[#1248e8] hover:text-[#0f3dc4] sm:inline">Try →</Link>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

function Highlight({
    icon: Icon,
    title,
    body,
}: {
    icon: typeof Play;
    title: string;
    body: string;
}) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5">
            <Icon className="size-5 text-[#1248e8]" />
            <h2 className="mt-3 font-semibold text-slate-950">{title}</h2>
            <p className="mt-1 text-sm leading-6 text-slate-600">{body}</p>
        </div>
    );
}
