import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { endpointFamilies } from '@/lib/ukapi-content';

export default function ApiCatalogue() {
    return (
        <>
            <Head title="API catalogue">
                <meta
                    name="description"
                    content="Explore the UKApi.co.uk endpoint catalogue and its geographic coverage."
                />
            </Head>
            <PageHero
                eyebrow="API catalogue · v1"
                title="The UK data field index."
                description="Live endpoints grouped by the job they do. Coverage, source and freshness remain visible at the point of use."
            />
            <section className="atlas-shell pb-24">
                <div className="border-t border-l border-slate-950">
                    {endpointFamilies.map((family, index) => (
                        <article
                            key={family.slug}
                            className="grid border-r border-b border-slate-950 bg-white lg:grid-cols-[5rem_15rem_1fr_16rem]"
                        >
                            <div className="border-b border-slate-300 p-5 font-mono text-xs text-[#2563c7] lg:border-r lg:border-b-0">
                                {String(index + 1).padStart(2, '0')}
                            </div>
                            <div className="border-b border-slate-300 p-5 lg:border-r lg:border-b-0">
                                <h2 className="font-sans text-xl font-bold tracking-tight">
                                    {family.name}
                                </h2>
                                <p className="mt-3 text-sm leading-6 text-slate-600">
                                    {family.summary}
                                </p>
                            </div>
                            <div className="border-b border-slate-300 p-5 lg:border-r lg:border-b-0">
                                <div className="space-y-2">
                                    {family.endpoints
                                        .slice(0, 4)
                                        .map((endpoint) => (
                                            <p
                                                key={endpoint.path}
                                                className="flex min-w-0 gap-3 font-mono text-[11px] leading-5 text-slate-600"
                                            >
                                                <span className="shrink-0 font-bold text-[#2563c7]">
                                                    GET
                                                </span>
                                                <span className="truncate">
                                                    {endpoint.path}
                                                </span>
                                            </p>
                                        ))}
                                </div>
                            </div>
                            <div className="flex items-center justify-between gap-5 p-5">
                                <div>
                                    <p className="text-2xl font-semibold">
                                        {family.endpoints.length}
                                    </p>
                                    <p className="font-mono text-[9px] tracking-wider text-slate-500 uppercase">
                                        live endpoints
                                    </p>
                                </div>
                                <Link
                                    href={`/docs/api-reference#${family.slug}`}
                                    className="grid size-11 place-items-center border border-slate-950 transition hover:bg-[#2563c7] hover:text-white"
                                    aria-label={`Read ${family.name} reference`}
                                >
                                    <ArrowRight className="size-4" />
                                </Link>
                            </div>
                        </article>
                    ))}
                </div>
                <div className="mt-8 flex flex-col justify-between gap-4 border-t border-slate-300 pt-5 font-mono text-[10px] tracking-[0.08em] text-slate-500 uppercase sm:flex-row">
                    <span>All routes use the /v1 contract</span>
                    <Link
                        href="/data-sources"
                        className="font-bold text-slate-950 hover:text-[#2563c7]"
                    >
                        Review source attribution →
                    </Link>
                </div>
            </section>
        </>
    );
}

export function PageHero({
    eyebrow,
    title,
    description,
}: {
    eyebrow: string;
    title: string;
    description: string;
}) {
    return (
        <section className="atlas-shell py-16 sm:py-20 lg:py-24">
            <div className="grid gap-8 lg:grid-cols-[.42fr_1.58fr]">
                <div className="pt-2">
                    <p className="atlas-kicker">{eyebrow}</p>
                </div>
                <div>
                    <h1 className="atlas-title max-w-4xl">{title}</h1>
                    <p className="mt-7 max-w-2xl text-lg leading-8 text-slate-600">
                        {description}
                    </p>
                </div>
            </div>
            <div className="mt-12 border-t border-slate-950" />
        </section>
    );
}
