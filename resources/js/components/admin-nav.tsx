import { Link } from '@inertiajs/react';

const items = [
    ['Overview', '/admin'],
    ['Users', '/admin/users'],
    ['Usage', '/admin/usage'],
    ['Providers', '/admin/providers'],
    ['Errors', '/admin/errors'],
    ['Feature flags', '/admin/feature-flags'],
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
                    className="rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-blue-50 hover:text-[#1248e8]"
                >
                    {name}
                </Link>
            ))}
        </nav>
    );
}
