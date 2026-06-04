import { usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;
    const { palette, rgb } = useUiTheme();
    const shellStyle = {
        '--background': rgb(palette.secondary['50']),
        '--foreground': rgb(palette.primary['800']),
        '--card': rgb(palette.secondary['50']),
        '--card-foreground': rgb(palette.primary['800']),
        '--popover': rgb(palette.secondary['50']),
        '--popover-foreground': rgb(palette.primary['800']),
        '--primary': rgb(palette.primary['700']),
        '--primary-foreground': rgb(palette.secondary['50']),
        '--secondary': rgb(palette.secondary['100']),
        '--secondary-foreground': rgb(palette.primary['800']),
        '--muted': rgb(palette.secondary['100']),
        '--muted-foreground': rgb(palette.primary['500']),
        '--accent': rgb(palette.primary['100']),
        '--accent-foreground': rgb(palette.primary['800']),
        '--border': rgb(palette.primary['100']),
        '--input': rgb(palette.primary['100']),
        '--ring': rgb(palette.tertiary['500']),
        '--sidebar': rgb(palette.primary['800']),
        '--sidebar-foreground': rgb(palette.secondary['50']),
        '--sidebar-primary': rgb(palette.tertiary['500']),
        '--sidebar-primary-foreground': rgb(palette.primary['800']),
        '--sidebar-accent': rgb(palette.primary['600']),
        '--sidebar-accent-foreground': rgb(palette.secondary['50']),
        '--sidebar-border': rgb(palette.primary['600']),
        '--sidebar-ring': rgb(palette.tertiary['500']),
    } as CSSProperties;

    if (variant === 'header') {
        return (
            <div
                className="flex min-h-screen w-full flex-col"
                style={shellStyle}
            >
                {children}
            </div>
        );
    }

    return (
        <SidebarProvider
            defaultOpen={isOpen}
            style={shellStyle}
            className="bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.75),transparent_22%),linear-gradient(180deg,var(--background)_0%,rgb(248_250_252)_100%)]"
        >
            {children}
        </SidebarProvider>
    );
}
