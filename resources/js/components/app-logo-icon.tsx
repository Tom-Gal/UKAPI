import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            fill="none"
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
    );
}
