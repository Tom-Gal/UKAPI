import type { ReactNode } from 'react';

export function DashboardPageHeader({
    eyebrow,
    title,
    description,
    action,
}: {
    eyebrow?: string;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col justify-between gap-5 border-b border-slate-950 bg-[#f5f7f4] px-5 py-8 md:flex-row md:items-end lg:px-8 lg:py-10">
            <div>
                {eyebrow && <p className="atlas-kicker">{eyebrow}</p>}
                <h1 className="mt-3 text-4xl font-semibold tracking-[-0.04em] text-slate-950">
                    {title}
                </h1>
                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    {description}
                </p>
            </div>
            {action}
        </div>
    );
}

export function EmptyState({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="border border-dashed border-slate-400 bg-white p-8 text-center">
            <h2 className="font-sans font-semibold text-slate-900">{title}</h2>
            <div className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">
                {children}
            </div>
        </div>
    );
}
