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
            <div className="atlas-shell max-w-5xl pb-24">
                <div className="grid border border-slate-950 bg-[#dbe4cd] p-5 text-sm leading-6 sm:grid-cols-[3rem_1fr]">
                    <Activity className="mb-2 size-5 text-[#2563c7]" />
                    <strong>Availability</strong>
                    <br />
                    Source-specific failures are returned by the relevant API
                    request with a request ID for support follow-up.
                </div>
                <div className="mt-6 divide-y divide-slate-300 border border-slate-950 bg-white shadow-[5px_5px_0_#d7d2c3]">
                    {services.map((service) => (
                        <StatusRow key={service.name} service={service} />
                    ))}
                </div>
                <p className="mt-6 text-sm leading-6 text-slate-500">
                    Read the{' '}
                    <Link
                        href="/data-sources"
                        className="border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                    >
                        source coverage notes
                    </Link>{' '}
                    for coverage and freshness information.
                </p>
            </div>
        </>
    );
}

function StatusRow({ service }: { service: Service }) {
    const Icon = service.state === 'available' ? CheckCircle2 : CircleAlert;

    return (
        <div className="flex items-center justify-between gap-4 p-5 transition hover:bg-[#e4ece7]">
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
