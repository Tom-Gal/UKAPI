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
        <div className="flex flex-col justify-between gap-4 border-b border-slate-200 px-5 py-7 md:flex-row md:items-end lg:px-8">
            <div>
                {eyebrow && (
                    <p className="text-xs font-bold tracking-[.16em] text-[#1248e8] uppercase">
                        {eyebrow}
                    </p>
                )}
                <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
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
        <div className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h2 className="font-semibold text-slate-900">{title}</h2>
            <div className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">
                {children}
            </div>
        </div>
    );
}
