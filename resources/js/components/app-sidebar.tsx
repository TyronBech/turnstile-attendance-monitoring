import { Link } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { useUiTheme } from '@/hooks/use-ui-theme';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    const { palette, rgb } = useUiTheme();

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="border-0 bg-transparent [&>[data-sidebar=sidebar]]:bg-transparent"
        >
            <div
                className="flex h-full flex-col overflow-hidden border-r"
                style={{
                    background: `linear-gradient(180deg, ${rgb(palette.primary['800'])} 0%, ${rgb(palette.primary['700'])} 58%, ${rgb(palette.primary['600'])} 100%)`,
                    borderColor: rgb(palette.secondary['50'], 0.14),
                }}
            >
                <SidebarHeader
                    style={{
                        borderBottom: `1px solid ${rgb(palette.secondary['50'], 0.12)}`,
                    }}
                >
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={dashboard()} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent>
                    <NavMain items={mainNavItems} />
                </SidebarContent>

                <SidebarFooter
                    style={{
                        borderTop: `1px solid ${rgb(palette.secondary['50'], 0.08)}`,
                    }}
                >
                    <NavUser />
                </SidebarFooter>
            </div>
        </Sidebar>
    );
}
