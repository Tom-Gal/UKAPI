import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            legal: {
                entityName: string | null;
                companyNumber: string | null;
                registeredOffice: string | null;
                contactEmail: string;
                icoNumber: string | null;
                lastUpdated: string;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
