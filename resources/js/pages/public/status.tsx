import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, CircleAlert } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

type Service = {
    name: string;
    detail: string;
    state: 'available' | 'unavailable';
};

export default function Status({ services }: { services: Service[] }) {
    return (
        <>
            <Head title="Service status">
                <meta
                    name="description"
                    content="UKAPI.io service and provider status."
                />
            </Head>
            <PageHero
                eyebrow="Service status"
                title="Current availability across UKAPI.io."
                description="This page shows the application capabilities configured on this deployment."
            />
            <div className="mx-auto max-w-4xl px-5 pb-20 lg:px-8">
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-950">
                    <Activity className="mb-2 size-5 text-[#1248e8]" />
                    <strong>Availability</strong>
                    <br />
                    Source-specific failures are returned by the relevant API
                    request with a request ID for support follow-up.
                </div>
                <div className="mt-6 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                    {services.map((service) => (
                        <StatusRow key={service.name} service={service} />
                    ))}
                </div>
                <p className="mt-6 text-sm leading-6 text-slate-500">
                    Read the{' '}
                    <Link
                        href="/data-sources"
                        className="font-semibold text-[#1248e8]"
                    >
                    source coverage notes</Link>{' '}
                    for coverage and freshness information.
                </p>
            </div>
        </>
    );
}

function StatusRow({ service }: { service: Service }) {
    const Icon = service.state === 'available' ? CheckCircle2 : CircleAlert;

    return (
        <div className="flex items-center justify-between gap-4 p-5">
            <div className="flex items-center gap-3">
                <Icon className="size-4 text-slate-500" />
                <span className="font-medium">{service.name}</span>
            </div>
            <span
                className={`text-sm font-medium ${service.state === 'available' ? 'text-emerald-700' : 'text-amber-700'}`}
            >
                {service.detail}
            </span>
        </div>
    );
}
