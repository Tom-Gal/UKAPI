import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    Braces,
    Check,
    Database,
    MapPinned,
    ShieldCheck,
} from 'lucide-react';
import { endpointFamilies, plans } from '@/lib/ukapi-content';

const example = `curl https://api.ukapi.io/v1/postcodes/BL2\n  -H "Authorization: Bearer uk_live_…"\n\n{\n  "data": { "postcode": "BL2 6XX", "country": "England" },\n  "meta": { "request_id": "req_01…", "cached": true }\n}`;

export default function Welcome() {
    return (
        <>
            <Head title="Simple UK data. One API.">
                <meta
                    name="description"
                    content="Postcodes, companies, councils, crime, holidays, planning and more through one clean API."
                />
            </Head>
            <section className="relative overflow-hidden border-b border-slate-200 bg-[radial-gradient(circle_at_75%_25%,#dce8ff,transparent_26rem)]">
                <div
                    aria-hidden="true"
                    className="ukapi-glow pointer-events-none absolute -top-28 -right-24 size-96 rounded-full bg-blue-300/25 blur-3xl"
                />
                <div className="relative mx-auto grid max-w-6xl gap-12 px-5 py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-28">
                    <div className="max-w-xl">
                        <p className="ukapi-enter mb-5 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold tracking-wide text-blue-800">
                            <span className="size-1.5 rounded-full bg-[#1248e8]" />
                            UK data, normalised for developers
                        </p>
                        <h1 className="ukapi-enter ukapi-enter-delay-1 text-5xl font-semibold tracking-[-0.055em] text-slate-950 sm:text-6xl">
                            Simple UK data.
                            <br />
                            <span className="text-[#1248e8]">One API.</span>
                        </h1>
                        <p className="ukapi-enter ukapi-enter-delay-2 mt-6 max-w-lg text-lg leading-8 text-slate-600">
                            Postcodes, companies, councils, crime, holidays,
                            planning and more through one clean API.
                        </p>
                        <div className="ukapi-enter ukapi-enter-delay-3 mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/register"
                                className="inline-flex items-center gap-2 rounded-lg bg-[#1248e8] px-4 py-3 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-[#0f3dc4] hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                            >
                                Start with the free plan{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                            <Link
                                href="/docs"
                                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 transition duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                            >
                                Read the docs <BookOpen className="size-4" />
                            </Link>
                        </div>
                        <p className="ukapi-enter ukapi-enter-delay-4 mt-5 text-sm text-slate-500">
                            5,000 requests each month. No credit card required.
                        </p>
                    </div>
                    <div className="ukapi-enter ukapi-enter-delay-2 ukapi-float self-center overflow-hidden rounded-xl border border-slate-700 bg-[#10182d] shadow-2xl shadow-blue-950/15">
                        <div className="flex items-center gap-2 border-b border-slate-700 px-4 py-3">
                            <span className="size-2 rounded-full bg-rose-400" />
                            <span className="size-2 rounded-full bg-amber-300" />
                            <span className="size-2 rounded-full bg-emerald-400" />
                            <span className="ml-2 font-mono text-xs text-slate-400">
                                postcode.ts
                            </span>
                        </div>
                        <pre className="overflow-x-auto p-5 font-mono text-[13px] leading-6 text-blue-100">
                            <code>{example}</code>
                        </pre>
                    </div>
                </div>
            </section>
            <section className="mx-auto max-w-6xl px-5 py-20 lg:px-8">
                <div className="max-w-2xl">
                    <p className="text-sm font-bold tracking-[.16em] text-[#1248e8] uppercase">
                        One surface area
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-[-.035em]">
                        The useful parts of UK data, without the integration
                        tax.
                    </h2>
                    <p className="mt-4 leading-7 text-slate-600">
                        One account, one API key, one response format.
                        Source-specific quirks stay behind the API boundary.
                    </p>
                </div>
                <div className="mt-10 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                    {endpointFamilies.map((family, index) => (
                        <Link
                            key={family.slug}
                            href="/docs"
                            className="group bg-white p-5 transition duration-300 hover:-translate-y-1 hover:bg-blue-50 hover:shadow-lg hover:shadow-blue-950/5 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#1248e8]"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-mono text-xs text-slate-400">
                                    0{index + 1}
                                </span>
                                <ArrowRight className="size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#1248e8]" />
                            </div>
                            <h3 className="mt-8 font-semibold tracking-tight">
                                {family.name}
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">
                                {family.summary}
                            </p>
                        </Link>
                    ))}
                </div>
            </section>
            <section className="border-y border-slate-200 bg-white">
                <div className="mx-auto grid max-w-6xl gap-10 px-5 py-20 md:grid-cols-3 lg:px-8">
                    <Value
                        icon={Braces}
                        title="Predictable contracts"
                        text="A consistent JSON envelope, stable error codes and clear versioning under /v1."
                    />
                    <Value
                        icon={Database}
                        title="Useful freshness"
                        text="Caching policies are documented by endpoint, with source timestamps where they matter."
                    />
                    <Value
                        icon={MapPinned}
                        title="Honest coverage"
                        text="England-only, partial and UK-wide coverage are explicit—not hidden in fine print."
                    />
                </div>
            </section>
            <section className="mx-auto max-w-6xl px-5 py-20 lg:px-8">
                <div className="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p className="text-sm font-bold tracking-[.16em] text-[#1248e8] uppercase">
                            Straightforward pricing
                        </p>
                        <h2 className="mt-3 text-3xl font-semibold tracking-[-.035em]">
                            A useful free tier. Clear limits.
                        </h2>
                    </div>
                    <Link
                        href="/pricing"
                        className="inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]"
                    >
                        Compare plans <ArrowRight className="size-4" />
                    </Link>
                </div>
                <div className="mt-8 grid gap-4 md:grid-cols-4">
                    {plans.map((plan) => (
                        <div
                            key={plan.name}
                            className={`rounded-xl border p-5 transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-blue-950/5 ${plan.emphasis ? 'border-[#1248e8] bg-blue-50' : 'border-slate-200 bg-white'}`}
                        >
                            <p className="font-semibold">{plan.name}</p>
                            <p className="mt-4 text-3xl font-semibold tracking-tight">
                                {plan.price}
                                <span className="text-sm font-medium text-slate-500">
                                    /mo
                                </span>
                            </p>
                            <p className="mt-3 font-mono text-sm text-[#1248e8]">
                                {plan.quota} requests
                            </p>
                            <p className="mt-3 text-sm leading-5 text-slate-600">
                                {plan.description}
                            </p>
                        </div>
                    ))}
                </div>
            </section>
            <section className="border-t border-slate-200 bg-[#10182d]">
                <div className="mx-auto flex max-w-6xl flex-col items-start justify-between gap-7 px-5 py-16 lg:flex-row lg:items-center lg:px-8">
                    <div>
                        <div className="flex items-center gap-2 text-blue-200">
                            <ShieldCheck className="size-5" />
                            Built for real integrations.
                        </div>
                        <h2 className="mt-3 text-3xl font-semibold tracking-[-.035em] text-white">
                            Make the first successful request in minutes.
                        </h2>
                    </div>
                    <Link
                        href="/register"
                        className="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Create your account <Check className="size-4" />
                    </Link>
                </div>
            </section>
        </>
    );
}

function Value({
    icon: Icon,
    title,
    text,
}: {
    icon: typeof Braces;
    title: string;
    text: string;
}) {
    return (
        <div>
            <Icon className="size-5 text-[#1248e8]" />
            <h3 className="mt-4 font-semibold">{title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </div>
    );
}
