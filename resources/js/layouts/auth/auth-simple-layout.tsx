import { Link } from '@inertiajs/react';
import { UkapiMark } from '@/components/ukapi-mark';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-[#f5f7f4] p-6 md:p-10">
            <div
                aria-hidden="true"
                className="absolute inset-0 [background-image:linear-gradient(to_right,#8fb49f35_1px,transparent_1px),linear-gradient(to_bottom,#8fb49f35_1px,transparent_1px)] [background-size:48px_48px] opacity-60"
            />
            <div className="relative w-full max-w-md border border-slate-950 bg-white p-7 shadow-[7px_7px_0_#9dbbec] sm:p-9">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href="/"
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <UkapiMark />
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {title}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                    <p className="text-center text-xs leading-5 text-slate-500">
                        UKAPI.io is an independent developer service and is not
                        affiliated with or endorsed by the UK Government.
                    </p>
                </div>
            </div>
        </div>
    );
}
