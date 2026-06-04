import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { OrgFooter } from '@/components/org-footer';
import { OrgHeader } from '@/components/org-header';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    const { palette, rgb } = useUiTheme();

    return (
        <AppShell variant="header">
            <div
                className="flex min-h-screen w-full flex-col text-slate-900"
                style={{
                    background: `radial-gradient(circle at top left, ${rgb(palette.secondary['100'])} 0%, ${rgb(palette.secondary['50'])} 42%, ${rgb(palette.primary['50'])} 100%)`,
                }}
            >
                <OrgHeader variant="inside" maxWidth="max-w-7xl" breadcrumbs={breadcrumbs} />
                <AppContent variant="header">{children}</AppContent>
                <OrgFooter maxWidth="max-w-7xl" />
            </div>
        </AppShell>
    );
}
