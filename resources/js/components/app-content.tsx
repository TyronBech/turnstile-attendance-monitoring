import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({ variant = 'sidebar', children, ...props }: Props) {
    const { palette, rgb } = useUiTheme();

    if (variant === 'sidebar') {
        return (
            <SidebarInset
                {...props}
                className="bg-transparent shadow-none"
                style={{
                    background: `linear-gradient(180deg, ${rgb(palette.secondary['50'])} 0%, ${rgb(palette.secondary['100'], 0.72)} 100%)`,
                }}
            >
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
            {...props}
        >
            {children}
        </main>
    );
}
