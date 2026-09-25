import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CircleCheck } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';
import { documentationGuides, endpointFamilies } from '@/lib/ukapi-content';

export default function Docs() {
    return (
        <>
            <Head title="Documentation">
                <meta
                    name="description"
                    content="Get started with UKAPI.io authentication, errors, rate limits and endpoint references."
                />
            </Head>
            <PageHero
                eyebrow="Documentation"
                title="Designed for the first successful request."
                description="Start with a test key, then make a real VAT, postcode, company or crime request. Each endpoint shows its availability, source, freshness and exact parameters."
            />
            <div className="mx-auto grid max-w-6xl gap-10 px-5 pb-20 lg:grid-cols-[.72fr_1.28fr] lg:px-8">
                <aside>
                    <p className="text-xs font-bold tracking-[.16em] text-slate-500 uppercase">
                        Guides
                    </p>
                    <nav
                        className="mt-3 grid gap-1"
                        aria-label="Documentation guides"
                    >
                        {documentationGuides.map(([slug, title]) => (
                            <Link
                                key={slug}
                                href={`/docs/${slug}`}
                                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50 hover:text-[#1248e8]"
                            >
                                {title}
                            </Link>
                        ))}
                    </nav>
                    <div className="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-950">
                        <CircleCheck className="mb-2 size-4" />
                        VAT, postcode, company and Police.uk crime endpoints are
                        live. Use a test key in their reference page to send a
                        request from this browser.
                    </div>
                </aside>
                <section>
                    <p className="text-xs font-bold tracking-[.16em] text-slate-500 uppercase">
                        Reference
                    </p>
                    <div className="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                        {endpointFamilies.map((family) => (
                            <div key={family.slug} className="p-5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 className="font-semibold">
                                            {family.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-slate-600">
                                            {family.summary}
                                        </p>
                                    </div>
                                    <Link
                                        href={`/docs/${family.slug}`}
                                        className="inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8]"
                                    >
                                        View reference{' '}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </div>
                                <div className="mt-4 grid gap-2">
                                    {family.endpoints.map((endpoint, index) => (
                                        <Link
                                            key={`${endpoint.path}-${index}`}
                                            href={`/docs/api/${family.slug}/${index + 1}`}
                                            className="truncate rounded-md bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700 hover:bg-blue-50"
                                        >
                                            <span className="mr-2 text-[#1248e8]">
                                                GET
                                            </span>
                                            {endpoint.path}
                                            {endpoint.live && (
                                                <span className="ml-2 rounded-full bg-emerald-100 px-1.5 py-0.5 font-sans text-[10px] font-bold tracking-wide text-emerald-800 uppercase">
                                                    Live
                                                </span>
                                            )}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
