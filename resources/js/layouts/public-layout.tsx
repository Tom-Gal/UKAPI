import { Link, usePage } from '@inertiajs/react';
import { useState, type PropsWithChildren } from 'react';
import { Menu, X } from 'lucide-react';
import { UkapiMark } from '@/components/ukapi-mark';

const navigation = [
    ['API catalogue', '/api-catalogue'],
    ['Documentation', '/docs'],
    ['Try it out', '/try-it'],
    ['Pricing', '/pricing'],
    ['Status', '/status'],
];

export default function PublicLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const { auth } = page.props;
    const currentPath = page.url.split('?')[0];
    const [menuOpen, setMenuOpen] = useState(false);

    const isActive = (href: string) =>
        currentPath === href ||
        (href === '/docs' && currentPath.startsWith('/docs/'));

    return (
        <div className="min-h-svh bg-[#f7f9fc] text-slate-950">
            <a
                href="#main-content"
                className="fixed top-3 left-3 z-[60] -translate-y-16 rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-transform focus:translate-y-0"
            >
                Skip to content
            </a>
            <header className="sticky top-0 z-50 border-b border-slate-200 bg-[#f7f9fc]/95 backdrop-blur">
                <nav
                    className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 lg:px-8"
                    aria-label="Main navigation"
                >
                    <Link
                        href="/"
                        aria-label="UKAPI.io home"
                        className="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1248e8]"
                    >
                        <UkapiMark />
                    </Link>
                    <div className="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
                        {navigation.map(([label, href]) => (
                            <Link
                                key={href}
                                href={href}
                                aria-current={
                                    isActive(href) ? 'page' : undefined
                                }
                                className={`rounded-md px-1 py-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] ${isActive(href) ? 'text-[#1248e8]' : 'hover:text-slate-950'}`}
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                    <div className="flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="rounded-lg bg-slate-950 px-3.5 py-2 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-950"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="hidden rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] sm:block"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-lg bg-[#1248e8] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-[#0f3dc4] hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                                >
                                    Get an API key
                                </Link>
                            </>
                        )}
                        <button
                            type="button"
                            className="rounded-md p-2 text-slate-600 transition-colors hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] md:hidden"
                            aria-expanded={menuOpen}
                            aria-controls="mobile-navigation"
                            onClick={() => setMenuOpen(!menuOpen)}
                        >
                            {menuOpen ? (
                                <X className="size-5" />
                            ) : (
                                <Menu className="size-5" />
                            )}
                            <span className="sr-only">Toggle navigation</span>
                        </button>
                    </div>
                </nav>
                {menuOpen && (
                    <div
                        id="mobile-navigation"
                        className="border-t border-slate-200 px-5 py-3 md:hidden"
                    >
                        <div className="mx-auto grid max-w-6xl grid-cols-2 gap-1">
                            {navigation.map(([label, href]) => (
                                <Link
                                    key={href}
                                    href={href}
                                    onClick={() => setMenuOpen(false)}
                                    aria-current={
                                        isActive(href) ? 'page' : undefined
                                    }
                                    className={`rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] ${isActive(href) ? 'bg-blue-50 text-[#1248e8]' : 'text-slate-700 hover:bg-blue-50 hover:text-[#1248e8]'}`}
                                >
                                    {label}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </header>
            <main id="main-content">{children}</main>
            <footer className="border-t border-slate-200 bg-white">
                <div className="mx-auto grid max-w-6xl gap-8 px-5 py-10 text-sm md:grid-cols-[1.3fr_1fr_1fr] lg:px-8">
                    <div className="space-y-3">
                        <UkapiMark />
                        <p className="max-w-sm leading-6 text-slate-600">
                            Useful UK data through one predictable API. Built
                            for developers, not paperwork.
                        </p>
                    </div>
                    <div className="grid content-start gap-2 text-slate-600">
                        <Link href="/docs" className="hover:text-[#1248e8]">
                            Documentation
                        </Link>
                        <Link
                            href="/data-sources"
                            className="hover:text-[#1248e8]"
                        >
                            Data sources & attribution
                        </Link>
                        <Link href="/status" className="hover:text-[#1248e8]">
                            Service status
                        </Link>
                    </div>
                    <div className="grid content-start gap-2 text-slate-600">
                        <Link href="/terms" className="hover:text-[#1248e8]">
                            Terms
                        </Link>
                        <Link href="/privacy" className="hover:text-[#1248e8]">
                            Privacy
                        </Link>
                        <Link
                            href="/acceptable-use"
                            className="hover:text-[#1248e8]"
                        >
                            Acceptable use
                        </Link>
                    </div>
                </div>
                <div className="mx-auto max-w-6xl border-t border-slate-100 px-5 py-5 text-xs leading-5 text-slate-500 lg:px-8">
                    UKAPI.io is an independent developer service and is not
                    affiliated with or endorsed by the UK Government.
                </div>
            </footer>
        </div>
    );
}
