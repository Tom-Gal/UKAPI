import { Head } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

type Source = {
    key: string;
    label: string;
    docs_url: string;
    licence_url: string;
    coverage: string;
    used_for: string;
    licence_summary: string;
    attribution: string;
};

export default function DataSources({ sources }: { sources: Source[] }) {
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
                title="Where the data comes from."
                description="See who publishes each dataset, what it covers, which licence applies and where to read the source documentation."
            />
            <div className="atlas-shell pb-24">
                <div className="overflow-x-auto border border-slate-950 bg-white shadow-[5px_5px_0_#d7d2c3]">
                    <table className="w-full min-w-[980px] text-left text-sm">
                        <thead className="border-b border-slate-950 bg-[#122019] font-mono text-[10px] tracking-[0.14em] text-[#dbe4cd] uppercase">
                            <tr>
                                <th className="px-5 py-3">Source</th>
                                <th className="px-5 py-3">Coverage</th>
                                <th className="px-5 py-3">Used for</th>
                                <th className="px-5 py-3">
                                    Licence & attribution
                                </th>
                                <th className="px-5 py-3">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sources.map((source) => (
                                <tr
                                    key={source.key}
                                    className="border-t border-slate-300 first:border-t-0 hover:bg-[#e4ece7]"
                                >
                                    <td className="px-5 py-4 font-medium">
                                        {source.label}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {source.coverage}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {source.used_for}
                                    </td>
                                    <td className="max-w-sm px-5 py-4 text-slate-600">
                                        <p>{source.licence_summary}</p>
                                        <p className="mt-2 text-xs text-slate-500">
                                            {source.attribution}
                                        </p>
                                    </td>
                                    <td className="px-5 py-4">
                                        <a
                                            href={source.docs_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                                        >
                                            Source docs{' '}
                                            <ExternalLink className="size-3.5" />
                                        </a>
                                        <a
                                            href={source.licence_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-2 inline-flex items-center gap-1 border-b border-[#2563c7] text-xs font-semibold text-slate-950 hover:text-[#2563c7]"
                                        >
                                            Licence reference{' '}
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
