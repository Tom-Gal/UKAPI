import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { endpointFamilies } from '@/lib/ukapi-content';

export default function ApiCatalogue() {
    return (
        <>
            <Head title="API catalogue">
                <meta
                    name="description"
                    content="Explore the UKAPI.io endpoint catalogue and its geographic coverage."
                />
            </Head>
            <PageHero
                eyebrow="API catalogue"
                title="Useful UK data, grouped by what you need to build."
                description="Every endpoint uses the /v1 contract. Coverage and source caveats remain visible at the point of use."
            />
            <div className="mx-auto max-w-6xl px-5 pb-20 lg:px-8">
                <div className="grid gap-4 md:grid-cols-2">
                    {endpointFamilies.map((family) => (
                        <article
                            key={family.slug}
                            className="rounded-xl border border-slate-200 bg-white p-6"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight">
                                        {family.name}
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-slate-600">
                                        {family.summary}
                                    </p>
                                </div>
                                <span className="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-500">
                                    {family.endpoints.length} endpoints
                                </span>
                            </div>
                            <div className="mt-5 space-y-2 border-t border-slate-100 pt-4">
                                {family.endpoints
                                    .slice(0, 3)
                                    .map((endpoint) => (
                                        <p
                                            key={endpoint.path}
                                            className="truncate font-mono text-xs text-slate-600"
                                        >
                                            <span className="mr-2 text-[#1248e8]">
                                                GET
                                            </span>
                                            {endpoint.path}
                                        </p>
                                    ))}
                            </div>
                            <Link
                                href="/docs"
                                className="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]"
                            >
                                Read reference <ArrowRight className="size-4" />
                            </Link>
                        </article>
                    ))}
                </div>
            </div>
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
        <section className="mx-auto max-w-6xl px-5 py-16 lg:px-8 lg:py-20">
            <p className="text-sm font-bold tracking-[.16em] text-[#1248e8] uppercase">
                {eyebrow}
            </p>
            <h1 className="mt-3 max-w-3xl text-4xl font-semibold tracking-[-.045em] sm:text-5xl">
                {title}
            </h1>
            <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                {description}
            </p>
        </section>
    );
}
