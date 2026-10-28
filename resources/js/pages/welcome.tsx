import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Check } from 'lucide-react';
import { endpointFamilies, enterprisePlan, plans } from '@/lib/ukapi-content';

const example = [
    'curl "https://api.ukapi.co.uk/v1/postcodes/BL2%206XX" -H "Authorization: Bearer YOUR_API_KEY" -H "Accept: application/json"',
    '',
    '{',
    '  "data": { "postcode": "BL2 6XX", "country": "England" },',
    '  "meta": { "request_id": "req_01…", "cached": true }',
    '}',
].join('\n');

export default function Welcome() {
    const liveEndpoints = endpointFamilies.reduce(
        (sum, family) => sum + family.endpoints.length,
        0,
    );

    return (
        <>
            <Head title="One clean route into useful UK data">
                <meta
                    name="description"
                    content="Postcodes, companies, crime, flood, MOT history and more through one carefully normalised API."
                />
            </Head>

            <section className="relative overflow-hidden border-b border-slate-950">
                <div
                    aria-hidden="true"
                    className="absolute inset-0 [background-image:linear-gradient(to_right,#8fb49f45_1px,transparent_1px),linear-gradient(to_bottom,#8fb49f45_1px,transparent_1px)] [background-size:64px_64px] opacity-45"
                />
                <div className="atlas-shell relative grid min-h-[690px] min-w-0 lg:grid-cols-[1.12fr_.88fr]">
                    <div className="flex flex-col justify-center border-slate-950 py-16 lg:border-r lg:py-24 lg:pr-14">
                        <div className="ukapi-enter flex items-center gap-3">
                            <span className="h-px w-10 bg-[#2563c7]" />
                            <p className="atlas-kicker">
                                The UK data field service
                            </p>
                        </div>
                        <h1 className="ukapi-enter ukapi-enter-delay-1 mt-7 max-w-3xl text-[clamp(3rem,7vw,6.5rem)] leading-[0.92] font-semibold tracking-[-0.06em]">
                            UK data, one clean API.
                        </h1>
                        <p className="ukapi-enter ukapi-enter-delay-2 mt-9 max-w-xl text-lg leading-8 text-slate-600 sm:text-xl">
                            Postcodes, companies, crime, flood, MOT history and
                            more, normalised into a single, dependable API for
                            UK products.
                        </p>
                        <div className="ukapi-enter ukapi-enter-delay-3 mt-9 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                            <Link
                                href="/register"
                                className="atlas-button w-full sm:w-auto"
                            >
                                Start on the free plan{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                            <Link
                                href="/docs"
                                className="atlas-button-secondary w-full sm:w-auto"
                            >
                                Open the field guide{' '}
                                <BookOpen className="size-4" />
                            </Link>
                        </div>
                        <p className="ukapi-enter ukapi-enter-delay-4 mt-6 font-mono text-[11px] tracking-[0.08em] text-slate-500 uppercase">
                            5,000 requests / month · no card required
                        </p>
                    </div>

                    <div className="relative flex min-w-0 items-center py-12 lg:pl-14">
                        <div className="w-full min-w-0">
                            <div className="atlas-coordinate mb-4 flex justify-between">
                                <span>Sample response</span>
                                <span className="hidden sm:inline">
                                    53.592° N · 2.412° W
                                </span>
                                <span className="sm:hidden">OS grid</span>
                            </div>
                            <div className="ukapi-enter ukapi-enter-delay-2 max-w-full min-w-0 border border-slate-950 bg-[#122019] text-[#eef1e9] shadow-[6px_6px_0_#9dbbec] sm:shadow-[8px_8px_0_#9dbbec]">
                                <div className="flex items-center justify-between border-b border-[#526158] px-5 py-3 font-mono text-[10px] tracking-[0.12em] text-[#afbcaf] uppercase">
                                    <span>Request / response</span>
                                    <span className="text-[#e89276]">
                                        200 OK
                                    </span>
                                </div>
                                <pre className="p-5 font-mono text-[12px] leading-6 break-words whitespace-pre-wrap sm:p-7 sm:text-[13px]">
                                    <code>{example}</code>
                                </pre>
                            </div>
                            <div className="mt-7 grid grid-cols-3 border border-slate-950 bg-white">
                                <Stat
                                    value={`${liveEndpoints}`}
                                    label="live endpoints"
                                />
                                <Stat
                                    value={`${endpointFamilies.length}`}
                                    label="data families"
                                />
                                <Stat value="v1" label="stable contract" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="atlas-shell py-20 lg:py-28">
                <div className="grid gap-12 lg:grid-cols-[.7fr_1.3fr]">
                    <div>
                        <p className="atlas-kicker">Coverage index</p>
                        <h2 className="mt-5 text-4xl leading-[1.02] font-semibold tracking-[-0.04em] sm:text-5xl">
                            The useful parts, catalogued clearly.
                        </h2>
                        <p className="mt-6 max-w-md leading-7 text-slate-600">
                            One account and one response shape. Source,
                            freshness and geographic limits remain attached to
                            every route.
                        </p>
                        <Link
                            href="/api-catalogue"
                            className="mt-7 inline-flex items-center gap-2 border-b border-slate-950 pb-1 text-sm font-bold hover:text-[#2563c7]"
                        >
                            Explore the full catalogue{' '}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                    <div className="border-t border-l border-slate-950">
                        {endpointFamilies.map((family, index) => (
                            <Link
                                key={family.slug}
                                href={`/docs/api-reference#${family.slug}`}
                                className="group grid gap-3 border-r border-b border-slate-950 bg-white p-5 transition hover:bg-[#e4ece7] sm:grid-cols-[3rem_11rem_1fr_auto] sm:items-center"
                            >
                                <span className="font-mono text-xs text-[#2563c7]">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                <h3 className="font-sans text-base font-bold tracking-tight">
                                    {family.name}
                                </h3>
                                <p className="text-sm leading-6 text-slate-600">
                                    {family.summary}
                                </p>
                                <ArrowRight className="size-4 transition group-hover:translate-x-1" />
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="border-y border-slate-950 bg-[#dbe4cd]">
                <div className="atlas-shell grid lg:grid-cols-[.72fr_1.28fr]">
                    <div className="border-b border-slate-950 py-14 lg:border-r lg:border-b-0 lg:py-20 lg:pr-14">
                        <p className="atlas-kicker">Contract field notes</p>
                        <h2 className="mt-5 max-w-lg text-4xl leading-[1.02] font-semibold tracking-[-0.04em] sm:text-5xl">
                            Upstream complexity stays upstream.
                        </h2>
                        <p className="mt-6 max-w-lg text-base leading-7 text-slate-700">
                            Providers disagree on authentication, schemas,
                            coverage and freshness. UKApi.co.uk turns those
                            differences into one documented contract while
                            keeping the important limits attached to the data.
                        </p>
                        <Link
                            href="/data-sources"
                            className="mt-7 inline-flex items-center gap-2 border-b border-slate-950 pb-1 text-sm font-bold hover:text-[#2563c7]"
                        >
                            Inspect the source register{' '}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>

                    <div className="py-12 lg:py-16 lg:pl-14">
                        <div className="border border-slate-950 bg-[#f5f7f4] shadow-[6px_6px_0_#8fb49f] sm:shadow-[8px_8px_0_#8fb49f]">
                            <div className="grid grid-cols-[1fr_auto_1fr_auto_1fr] items-center border-b border-slate-950 bg-[#122019] px-4 py-4 text-center font-mono text-[9px] font-bold tracking-[0.12em] text-[#dce5d8] uppercase sm:px-6 sm:text-[10px]">
                                <span>Provider</span>
                                <ArrowRight
                                    className="size-3.5 text-[#6ea1f2]"
                                    aria-hidden="true"
                                />
                                <span>UKApi layer</span>
                                <ArrowRight
                                    className="size-3.5 text-[#6ea1f2]"
                                    aria-hidden="true"
                                />
                                <span>/v1 contract</span>
                            </div>
                            <ContractField
                                field="source"
                                value="Named provider, never an anonymous payload"
                            />
                            <ContractField
                                field="coverage"
                                value="UK, Great Britain, England or partial, stated plainly"
                            />
                            <ContractField
                                field="freshness"
                                value="Source timestamp and cache state where available"
                            />
                            <ContractField
                                field="request_id"
                                value="A traceable identifier on every response"
                            />
                            <div className="grid gap-2 border-t border-slate-950 bg-white px-5 py-4 sm:grid-cols-[8rem_1fr] sm:items-center sm:px-6">
                                <span className="font-mono text-[10px] font-bold tracking-[0.12em] text-[#2563c7] uppercase">
                                    Result
                                </span>
                                <code className="min-w-0 overflow-x-auto font-mono text-xs font-bold text-slate-800">
                                    {
                                        '{ data, meta: { request_id, source, coverage } }'
                                    }
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="border-t border-slate-950 bg-[#eef2ed]">
                <div className="atlas-shell py-20 lg:py-28">
                    <div className="grid gap-8 border-b border-slate-950 pb-10 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <p className="atlas-kicker">Usage bands</p>
                            <h2 className="mt-5 text-4xl leading-none font-semibold tracking-[-0.04em] sm:text-5xl">
                                Start useful. Scale plainly.
                            </h2>
                            <p className="mt-5 max-w-2xl leading-7 text-slate-600">
                                Every live endpoint counts as one request. Pick
                                a monthly allowance; the API contract stays the
                                same. Quotas are hard at launch, with no overage
                                billing.
                            </p>
                        </div>
                        <Link
                            href="/pricing"
                            className="atlas-button-secondary"
                        >
                            Compare every plan <ArrowRight className="size-4" />
                        </Link>
                    </div>

                    <div className="mt-10 border-t border-l border-slate-950">
                        <div className="grid sm:grid-cols-2 lg:grid-cols-4">
                            {plans.map((plan) => (
                                <article
                                    key={plan.name}
                                    className={`relative grid grid-rows-[3rem_9rem_7rem_minmax(7.5rem,1fr)] border-r border-b border-slate-950 ${plan.emphasis ? 'bg-[#122019] text-white shadow-[inset_0_5px_0_#2563c7]' : 'bg-white'}`}
                                >
                                    <div className="flex items-start justify-between gap-3 px-6 pt-6 sm:px-7">
                                        <h3 className="font-mono text-[11px] font-bold tracking-[0.16em] uppercase">
                                            {plan.name}
                                        </h3>
                                        {plan.emphasis && (
                                            <span className="font-mono text-[9px] font-bold tracking-[0.12em] text-[#8fb49f] uppercase">
                                                Most useful
                                            </span>
                                        )}
                                    </div>

                                    <dl className="contents">
                                        <div className="border-b border-current/20 px-6 pt-6 sm:px-7">
                                            <dt className="font-mono text-[9px] tracking-[0.14em] uppercase opacity-55">
                                                Monthly price
                                            </dt>
                                            <dd className="mt-2 text-5xl font-semibold tracking-[-0.04em]">
                                                {plan.price}
                                                <span className="ml-1 text-sm font-normal tracking-normal opacity-55">
                                                    / mo
                                                </span>
                                            </dd>
                                        </div>
                                        <div className="border-b border-current/20 px-6 pt-5 sm:px-7">
                                            <dt className="font-mono text-[9px] tracking-[0.14em] uppercase opacity-55">
                                                Request allowance
                                            </dt>
                                            <dd
                                                className={`mt-2 font-mono text-sm font-bold ${plan.emphasis ? 'text-[#b9cba6]' : 'text-[#2563c7]'}`}
                                            >
                                                {plan.quota} / month
                                            </dd>
                                        </div>
                                    </dl>

                                    <div className="px-6 pt-5 sm:px-7">
                                        <p className="font-mono text-[9px] tracking-[0.14em] uppercase opacity-55">
                                            Designed for
                                        </p>
                                        <p className="mt-2 text-sm leading-6 opacity-75">
                                            {plan.description}
                                        </p>
                                    </div>
                                </article>
                            ))}
                        </div>

                        <div className="grid border-r border-b border-slate-950 bg-[#dbe4cd] lg:grid-cols-[.65fr_1.35fr_auto] lg:items-center">
                            <div className="border-b border-slate-950 p-6 sm:p-7 lg:border-r lg:border-b-0">
                                <p className="font-mono text-[10px] font-bold tracking-[0.16em] text-[#2563c7] uppercase">
                                    Custom route
                                </p>
                                <h3 className="mt-2 text-2xl font-semibold tracking-tight">
                                    {enterprisePlan.name}
                                </h3>
                            </div>
                            <div className="border-b border-slate-950 p-6 sm:p-7 lg:border-r lg:border-b-0">
                                <p className="max-w-2xl text-sm leading-6 text-slate-700">
                                    {enterprisePlan.description}
                                </p>
                                <p className="mt-3 font-mono text-[10px] leading-5 tracking-[0.06em] text-slate-600 uppercase">
                                    {enterprisePlan.features.join(' · ')}
                                </p>
                            </div>
                            <div className="p-6 sm:p-7">
                                <a
                                    href="mailto:contact@ukapi.co.uk?subject=Enterprise%20API%20enquiry"
                                    className="atlas-button w-full whitespace-nowrap lg:w-auto"
                                >
                                    Discuss enterprise{' '}
                                    <ArrowRight className="size-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="border-t border-slate-950 bg-[#2563c7] text-white">
                <div className="atlas-shell flex flex-col items-start justify-between gap-8 py-14 md:flex-row md:items-end">
                    <div>
                        <p className="font-mono text-[11px] font-bold tracking-[0.16em] uppercase">
                            Ready for first contact?
                        </p>
                        <h2 className="mt-4 max-w-2xl text-4xl leading-none font-semibold tracking-[-0.04em] sm:text-5xl">
                            Make a successful request in minutes.
                        </h2>
                    </div>
                    <Link
                        href="/register"
                        className="inline-flex min-h-12 items-center gap-2 border border-white bg-white px-5 py-3 text-sm font-bold text-[#122019] transition hover:bg-[#edf4ff]"
                    >
                        Create an account <Check className="size-4" />
                    </Link>
                </div>
            </section>
        </>
    );
}

function Stat({ value, label }: { value: string; label: string }) {
    return (
        <div className="border-r border-slate-950 p-4 last:border-r-0">
            <p className="text-2xl font-semibold tracking-tight">{value}</p>
            <p className="mt-1 font-mono text-[9px] tracking-wider text-slate-500 uppercase">
                {label}
            </p>
        </div>
    );
}

function ContractField({ field, value }: { field: string; value: string }) {
    return (
        <div className="grid gap-2 border-b border-slate-300 px-5 py-4 last:border-b-0 sm:grid-cols-[8rem_1fr] sm:items-center sm:px-6">
            <code className="font-mono text-xs font-bold text-[#2563c7]">
                {field}
            </code>
            <p className="text-sm leading-6 text-slate-700">{value}</p>
        </div>
    );
}
