import type { UiSettingsTheme } from '@/hooks/use-ui-theme';
import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            uiSettings: UiSettingsTheme;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
