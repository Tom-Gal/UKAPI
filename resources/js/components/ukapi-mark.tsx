import { cn } from '@/lib/utils';

export function UkapiMark({
    compact = false,
    className,
}: {
    compact?: boolean;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-2.5 font-semibold tracking-tight text-slate-950',
                className,
            )}
        >
            <span
                className="grid size-8 shrink-0 place-items-center text-slate-950"
                aria-hidden="true"
            >
                <svg
                    viewBox="0 0 32 32"
                    fill="none"
                    className="size-full"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M6.5 6.5V19c0 4.45 3.4 7.5 8.5 7.5s8.5-3.05 8.5-7.5V6.5"
                        stroke="currentColor"
                        strokeWidth="3.25"
                        strokeLinecap="round"
                    />
                    <circle cx="25" cy="7" r="3.25" fill="#1248e8" />
                    <path
                        d="M25 5.5v3M23.5 7h3"
                        stroke="white"
                        strokeWidth="1"
                        strokeLinecap="round"
                    />
                </svg>
            </span>
            {!compact && (
                <span className="text-[15px] leading-none tracking-[-0.055em]">
                    UKAPI<span className="text-[#1248e8]">.io</span>
                </span>
            )}
        </span>
    );
}
