import { Head } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

const sources = [
    [
        'Postcodes.io',
        'https://postcodes.io/docs/overview/',
        'UK',
        'Postcode and coordinate lookups',
    ],
    [
        'Companies House',
        'https://developer.company-information.service.gov.uk/',
        'UK',
        'Company profiles, officer and filing metadata; locally refreshed condensed SIC 2007 reference snapshot',
    ],
    [
        'Police.uk',
        'https://data.police.uk/docs/',
        'England, Wales and Northern Ireland; Scotland has British Transport Police data only',
        'Approximate, anonymised street-level crime records and crime categories (Open Government Licence v3.0)',
    ],
    [
        'Environment Agency flood-monitoring',
        'https://environment.data.gov.uk/flood-monitoring/doc/reference',
        'England',
        'Near-real-time warnings, monitoring stations and bounded station readings (Open Government Licence v3.0; beta source; not an emergency alert service)',
    ],
];

export default function DataSources() {
    return (
        <>
            <Head title="Data sources & attribution">
                <meta
                    name="description"
                    content="UKApi.co.uk source, attribution and coverage register."
                />
            </Head>
            <PageHero
                eyebrow="Data sources & attribution"
                title="Public data still has provenance, terms and limits."
                description="UKApi.co.uk is a normalisation layer, not the authority for these datasets. Coverage and freshness are exposed alongside endpoint responses."
            />
            <div className="atlas-shell pb-24">
                <div className="overflow-x-auto border border-slate-950 bg-white shadow-[5px_5px_0_#d7d2c3]">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b border-slate-950 bg-[#122019] font-mono text-[10px] tracking-[0.14em] text-[#dbe4cd] uppercase">
                            <tr>
                                <th className="px-5 py-3">Source</th>
                                <th className="px-5 py-3">Coverage</th>
                                <th className="px-5 py-3">Used for</th>
                                <th className="px-5 py-3">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sources.map(([name, url, coverage, use]) => (
                                <tr
                                    key={name}
                                    className="border-t border-slate-300 first:border-t-0 hover:bg-[#e4ece7]"
                                >
                                    <td className="px-5 py-4 font-medium">
                                        {name}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {coverage}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {use}
                                    </td>
                                    <td className="px-5 py-4">
                                        <a
                                            href={url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                                        >
                                            Source docs{' '}
                                            <ExternalLink className="size-3.5" />
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <p className="mt-7 max-w-2xl border-l-2 border-[#2563c7] pl-4 text-sm leading-6 text-slate-600">
                    Data is supplied by the sources shown above. Coverage and
                    freshness are included in API responses where available.
                </p>
            </div>
        </>
    );
}
