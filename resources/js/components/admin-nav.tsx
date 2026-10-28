import { Link } from '@inertiajs/react';

const items = [
    ['Overview', '/admin'],
    ['Users', '/admin/users'],
    ['Providers', '/admin/providers'],
    ['Operations', '/admin/operations'],
    ['Testing', '/admin/testing'],
];

export function AdminNav() {
    return (
        <nav
            className="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-3 lg:px-8"
            aria-label="Administration"
        >
            {items.map(([name, href]) => (
                <Link
                    key={href}
                    href={href}
                    className="border-b border-transparent px-3 py-2 font-mono text-xs font-bold tracking-wide text-slate-600 uppercase hover:border-[#2563c7] hover:text-[#2563c7]"
                >
                    {name}
                </Link>
            ))}
        </nav>
    );
}
