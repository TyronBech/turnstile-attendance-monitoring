import { useUiTheme } from '@/hooks/use-ui-theme';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { palette, rgb } = useUiTheme();

    return (
        <header
            className="flex h-16 shrink-0 items-center gap-2 border-b px-6 text-white transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
            style={{
                background: `linear-gradient(135deg, ${rgb(palette.primary['800'])} 0%, ${rgb(palette.primary['700'])} 60%, ${rgb(palette.primary['600'])} 100%)`,
                borderColor: rgb(palette.secondary['50'], 0.1),
            }}
        >
            <div className="flex items-center gap-2">
                <SidebarTrigger
                    className="-ml-1"
                    style={{
                        color: rgb(palette.secondary['50']),
                    }}
                />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
