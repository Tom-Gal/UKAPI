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
                    <rect
                        x="2.5"
                        y="2.5"
                        width="27"
                        height="27"
                        stroke="currentColor"
                    />
                    <path
                        d="M8 9v8.2c0 4.2 2.8 6.8 7 6.8s7-2.6 7-6.8V9"
                        stroke="currentColor"
                        strokeWidth="2.4"
                    />
                    <circle cx="23.5" cy="8.5" r="3.5" fill="#2563c7" />
                </svg>
            </span>
            {!compact && (
                <span className="flex flex-col leading-none">
                    <span className="text-[15px] tracking-[-0.055em]">
                        UKAPI.io
                    </span>
                    <span className="mt-1 font-mono text-[8px] font-medium tracking-[0.18em] text-slate-500 uppercase">
                        Data field service
                    </span>
                </span>
            )}
        </span>
    );
}
