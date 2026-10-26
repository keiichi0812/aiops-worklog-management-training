import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, List } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
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
type Training = {
    title: string;
    homeUrl: string;
    masters: { title: string; url: string }[];
};

export function AppSidebar() {
    const { training } = usePage<{ training: Training }>().props;
    const items: NavItem[] = [
        { title: '研修の案内', href: dashboard(), icon: LayoutGrid },
        { title: training.title, href: training.homeUrl, icon: List },
        ...training.masters.map((item) => ({
            title: item.title,
            href: item.url,
            icon: List,
        })),
    ];
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()}>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
