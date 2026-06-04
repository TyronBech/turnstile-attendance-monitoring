import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, X, GraduationCap, UserCog, LayoutGrid, Upload, Settings, Users } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { cn, toUrl } from '@/lib/utils';
import { dashboard, login } from '@/routes';
import imports from '@/routes/imports';
import userMaintenance from '@/routes/user-maintenance';
import type { BreadcrumbItem } from '@/types';

interface OrgHeaderProps {
    variant?: 'public' | 'inside' | 'auth';
    maxWidth?: string;
    authTitle?: string;
    authDescription?: string;
    breadcrumbs?: BreadcrumbItem[];
}

export function OrgHeader({
    variant = 'public',
    maxWidth = 'max-w-6xl',
    authTitle,
    authDescription,
    breadcrumbs = [],
}: OrgHeaderProps) {
    const page = usePage();
    const { auth } = page.props as any;
    const getInitials = useInitials();
    const { theme, palette, rgb, orgInitial, orgName } = useUiTheme();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const primary700 = rgb(palette.primary['700']);
    const primary600 = rgb(palette.primary['600']);
    const primary500 = rgb(palette.primary['500']);
    const primary800 = rgb(palette.primary['800']);

    return (
        <>
            <header
                className="border-b px-6 py-4 text-white md:px-10 transition-all duration-300 relative shadow-md z-40"
                style={{
                    background: `linear-gradient(135deg, ${primary700} 0%, ${primary800} 100%)`,
                    borderColor: primary600,
                }}
            >
                <div className={`mx-auto flex h-14 items-center justify-between gap-4 ${maxWidth}`}>
                    {/* Logo and Brand */}
                    <div className="flex items-center gap-4">
                        <Link
                            href={toUrl(dashboard())}
                            className="flex h-12 w-12 items-center justify-center rounded-2xl border transition-transform hover:scale-105"
                            style={{
                                backgroundColor: rgb(palette.primary['500'], 0.18),
                                borderColor: rgb(palette.primary['500'], 0.42),
                            }}
                        >
                            {theme.logoUrl ? (
                                <img
                                    src={theme.logoUrl}
                                    alt={orgName}
                                    className="h-8 w-8 object-contain"
                                />
                            ) : (
                                <AppLogoIcon className="size-7 fill-current text-white" />
                            )}
                        </Link>
                        <div>
                            <p className="text-[10px] font-bold tracking-[0.38em] text-white/60 uppercase">
                                {orgInitial}
                            </p>
                            <h1 className="text-base font-bold tracking-tight text-white md:text-lg leading-tight">
                                {orgName}
                            </h1>
                        </div>
                    </div>

                    {/* Desktop Navigation Links */}
                    {variant === 'inside' && auth.user && (
                        <nav className="hidden lg:flex items-center gap-8 h-full">
                            <Link
                                href={toUrl(dashboard())}
                                className={cn(
                                    "flex items-center gap-2 text-sm font-medium transition-all pb-1.5 border-b-2 mt-1.5",
                                    isCurrentUrl(dashboard())
                                        ? "text-white border-white font-semibold"
                                        : "text-white/80 border-transparent hover:text-white hover:border-white/40"
                                )}
                            >
                                <LayoutGrid className="size-4" />
                                Dashboard
                            </Link>

                            <div className="relative group h-full flex items-center">
                                <button
                                    type="button"
                                    className={cn(
                                        "flex items-center gap-1.5 text-sm font-medium transition-all pb-1.5 border-b-2 cursor-pointer mt-1.5",
                                        isCurrentOrParentUrl('/imports')
                                            ? "text-white border-white font-semibold"
                                            : "text-white/80 border-transparent hover:text-white hover:border-white/40"
                                    )}
                                >
                                    <Upload className="size-4" />
                                    Imports
                                    <ChevronDown className="size-3.5 opacity-70 transition-transform group-hover:rotate-180" />
                                </button>
                                {/* Dropdown Menu */}
                                <div className="absolute left-0 top-full mt-1 w-52 rounded-xl shadow-xl bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1.5 scale-95 group-hover:scale-100 origin-top-left before:absolute before:-top-2 before:left-0 before:right-0 before:h-2 before:content-['']">
                                    <Link
                                        href={toUrl(imports.students.index())}
                                        className={cn(
                                            "flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors",
                                            isCurrentUrl(imports.students.index())
                                                ? "bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white font-semibold"
                                                : "text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        )}
                                    >
                                        <GraduationCap className="size-4.5 text-slate-500 dark:text-zinc-400" />
                                        Students
                                    </Link>
                                    <Link
                                        href={toUrl(imports.facultiesStaffs.index())}
                                        className={cn(
                                            "flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors",
                                            isCurrentUrl(imports.facultiesStaffs.index())
                                                ? "bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white font-semibold"
                                                : "text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        )}
                                    >
                                        <UserCog className="size-4.5 text-slate-500 dark:text-zinc-400" />
                                        Faculties & Staffs
                                    </Link>
                                </div>
                            </div>

                            <div className="relative group h-full flex items-center">
                                <button
                                    type="button"
                                    className={cn(
                                        "flex items-center gap-1.5 text-sm font-medium transition-all pb-1.5 border-b-2 cursor-pointer mt-1.5",
                                        isCurrentOrParentUrl('/user-maintenance')
                                            ? "text-white border-white font-semibold"
                                            : "text-white/80 border-transparent hover:text-white hover:border-white/40"
                                    )}
                                >
                                    <Settings className="size-4" />
                                    Maintenance
                                    <ChevronDown className="size-3.5 opacity-70 transition-transform group-hover:rotate-180" />
                                </button>
                                {/* Dropdown Menu */}
                                <div className="absolute left-0 top-full mt-1 w-52 rounded-xl shadow-xl bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1.5 scale-95 group-hover:scale-100 origin-top-left before:absolute before:-top-2 before:left-0 before:right-0 before:h-2 before:content-['']">
                                    <Link
                                        href={toUrl(userMaintenance.users.index())}
                                        className={cn(
                                            "flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors",
                                            isCurrentUrl(userMaintenance.users.index())
                                                ? "bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white font-semibold"
                                                : "text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        )}
                                    >
                                        <Users className="size-4.5 text-slate-500 dark:text-zinc-400" />
                                        Users
                                    </Link>
                                </div>
                            </div>
                        </nav>
                    )}

                    {/* Right Area (User Profile / Actions) */}
                    <div className="flex items-center gap-4 h-full">
                        {variant === 'public' && (
                            <nav className="flex items-center gap-4">
                                {auth.user ? (
                                    <Link
                                        href={toUrl(dashboard())}
                                        className="rounded-full px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                                        style={{ backgroundColor: primary500 }}
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={toUrl(login())}
                                        className="rounded-full border px-5 py-2 text-sm font-medium text-white/90 transition-colors hover:bg-white/10"
                                        style={{
                                            borderColor: rgb(palette.primary['500'], 0.55),
                                        }}
                                    >
                                        Log in
                                    </Link>
                                )}
                            </nav>
                        )}

                        {variant === 'inside' && auth.user && (
                            <div className="flex items-center gap-3">
                                {/* Profile Dropdown Menu */}
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <button
                                            type="button"
                                            className="flex items-center gap-2.5 p-1 rounded-full hover:bg-white/10 transition-colors duration-200 cursor-pointer focus:outline-none"
                                        >
                                            <Avatar className="size-8 overflow-hidden rounded-full border border-white/20">
                                                <AvatarImage
                                                    src={auth.user?.avatar}
                                                    alt={auth.user?.name}
                                                />
                                                <AvatarFallback className="rounded-full bg-white/20 text-white font-semibold text-xs">
                                                    {getInitials(auth.user?.name ?? '')}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span className="hidden md:inline-block text-sm font-medium text-white/90 group-hover:text-white">
                                                {auth.user?.name}
                                            </span>
                                            <ChevronDown className="hidden md:inline-block size-3.5 opacity-70" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent className="w-56 mt-1" align="end">
                                        {auth.user && (
                                            <UserMenuContent user={auth.user} />
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                {/* Mobile Hamburger menu button */}
                                <button
                                    type="button"
                                    onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                                    className="lg:hidden p-2 text-white/90 hover:text-white rounded-lg hover:bg-white/10 focus:outline-none cursor-pointer"
                                >
                                    {isMobileMenuOpen ? (
                                        <X className="size-5" />
                                    ) : (
                                        <Menu className="size-5" />
                                    )}
                                </button>
                            </div>
                        )}

                        {variant === 'auth' && (
                            <div className="hidden text-right text-sm text-white/72 md:block">
                                <p className="font-medium">{authTitle}</p>
                                <p>{authDescription}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Mobile Dropdown Panel */}
                {variant === 'inside' && auth.user && isMobileMenuOpen && (
                    <div className="lg:hidden mt-3 pt-3 border-t border-white/15 flex flex-col gap-2 transition-all duration-300">
                        <Link
                            href={toUrl(dashboard())}
                            onClick={() => setIsMobileMenuOpen(false)}
                            className={cn(
                                "flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors",
                                isCurrentUrl(dashboard())
                                    ? "bg-white/15 text-white"
                                    : "text-white/80 hover:bg-white/10 hover:text-white"
                            )}
                        >
                            <LayoutGrid className="size-4" />
                            Dashboard
                        </Link>

                        <div className="flex flex-col gap-1 pl-3 border-l border-white/15">
                            <span className="px-3 py-1 text-[10px] font-bold tracking-wider text-white/40 uppercase">
                                Imports
                            </span>
                            <Link
                                href={toUrl(imports.students.index())}
                                onClick={() => setIsMobileMenuOpen(false)}
                                className={cn(
                                    "flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors",
                                    isCurrentUrl(imports.students.index())
                                        ? "bg-white/15 text-white font-semibold"
                                        : "text-white/80 hover:bg-white/10 hover:text-white"
                                )}
                            >
                                <GraduationCap className="size-4" />
                                Students
                            </Link>
                            <Link
                                href={toUrl(imports.facultiesStaffs.index())}
                                onClick={() => setIsMobileMenuOpen(false)}
                                className={cn(
                                    "flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors",
                                    isCurrentUrl(imports.facultiesStaffs.index())
                                        ? "bg-white/15 text-white font-semibold"
                                        : "text-white/80 hover:bg-white/10 hover:text-white"
                                )}
                            >
                                <UserCog className="size-4" />
                                Faculties & Staffs
                            </Link>
                        </div>

                        <div className="flex flex-col gap-1 pl-3 border-l border-white/15">
                            <span className="px-3 py-1 text-[10px] font-bold tracking-wider text-white/40 uppercase">
                                Maintenance
                            </span>
                            <Link
                                href={toUrl(userMaintenance.users.index())}
                                onClick={() => setIsMobileMenuOpen(false)}
                                className={cn(
                                    "flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors",
                                    isCurrentUrl(userMaintenance.users.index())
                                        ? "bg-white/15 text-white font-semibold"
                                        : "text-white/80 hover:bg-white/10 hover:text-white"
                                )}
                            >
                                <Users className="size-4" />
                                Users
                            </Link>
                        </div>
                    </div>
                )}
            </header>

            {/* Breadcrumbs Bar */}
            {breadcrumbs && breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-slate-100 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-zinc-950/80">
                    <div className="mx-auto flex h-11 w-full items-center justify-start px-6 text-neutral-500 md:px-10 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
