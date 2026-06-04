import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    GraduationCap,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserCog,
    Users,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type {
    UserMaintenancePageProps,
    UserTab,
    UserWithDetails,
} from '@/types';
import { UserFormDialog } from '@/components/user-maintenance/user-form-dialog';
import { UserViewDialog } from '@/components/user-maintenance/user-view-dialog';

const DEBOUNCE_DELAY = 300;

export default function UsersIndex({
    users,
    tab,
    search: initialSearch,
    perPage: initialPerPage,
}: UserMaintenancePageProps) {
    const [searchValue, setSearchValue] = useState(initialSearch);
    const [perPage, setPerPage] = useState(initialPerPage);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Form dialog state
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserWithDetails | null>(null);

    // View dialog state
    const [isViewOpen, setIsViewOpen] = useState(false);
    const [viewingUser, setViewingUser] = useState<UserWithDetails | null>(null);

    // Delete dialog state
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);
    const [deletingUser, setDeletingUser] = useState<UserWithDetails | null>(null);

    const deleteForm = useForm({});

    /**
     * Handle search input with debounce.
     */
    const handleSearchChange = useCallback(
        (value: string) => {
            setSearchValue(value);

            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            debounceRef.current = setTimeout(() => {
                router.reload({
                    data: {
                        tab,
                        search: value,
                        per_page: perPage,
                        page: 1,
                    },
                    only: ['users', 'tab', 'search', 'perPage'],
                });
            }, DEBOUNCE_DELAY);
        },
        [tab, perPage],
    );

    // Cleanup debounce on unmount
    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    /**
     * Switch between Students and Employees tabs.
     */
    const handleTabChange = useCallback(
        (newTab: UserTab) => {
            setSearchValue('');
            router.reload({
                data: {
                    tab: newTab,
                    search: '',
                    per_page: perPage,
                    page: 1,
                },
                only: ['users', 'tab', 'search', 'perPage'],
            });
        },
        [perPage],
    );

    /**
     * Handle per-page value change with debounce.
     */
    const handlePerPageChange = useCallback(
        (value: number) => {
            const clamped = Math.max(1, Math.min(value, 100));
            setPerPage(clamped);

            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            debounceRef.current = setTimeout(() => {
                router.reload({
                    data: {
                        tab,
                        search: searchValue,
                        per_page: clamped,
                        page: 1,
                    },
                    only: ['users', 'tab', 'search', 'perPage'],
                });
            }, DEBOUNCE_DELAY);
        },
        [tab, searchValue],
    );

    /**
     * Navigate to a specific page.
     */
    const goToPage = useCallback(
        (page: number) => {
            router.reload({
                data: {
                    tab,
                    search: searchValue,
                    per_page: perPage,
                    page,
                },
                only: ['users', 'tab', 'search', 'perPage'],
            });
        },
        [tab, searchValue, perPage],
    );

    const handleAddUser = useCallback(() => {
        setEditingUser(null);
        setIsFormOpen(true);
    }, []);

    const handleEditUser = useCallback((user: UserWithDetails) => {
        setEditingUser(user);
        setIsFormOpen(true);
    }, []);

    const handleViewUser = useCallback((user: UserWithDetails) => {
        setViewingUser(user);
        setIsViewOpen(true);
    }, []);

    const handleDeleteUser = useCallback((user: UserWithDetails) => {
        setDeletingUser(user);
        setIsDeleteOpen(true);
    }, []);

    const confirmDelete = useCallback(() => {
        if (!deletingUser) {
            return;
        }

        deleteForm.delete(`/user-maintenance/users/${deletingUser.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('User deleted successfully.');
                setIsDeleteOpen(false);
                setDeletingUser(null);
            },
            onError: () => {
                toast.error('Failed to delete user. Please try again.');
            },
        });
    }, [deletingUser, deleteForm]);

    const isStudentsTab = tab === 'students';

    return (
        <>
            <Head title="User Maintenance" />

            <div className="px-6 py-10 md:px-10">
                <div className="space-y-6">
                    {/* Page Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <Users className="h-7 w-7 text-blue-600 dark:text-blue-400" />
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    User Maintenance
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Manage student and employee records.
                                </p>
                            </div>
                        </div>
                        <Button
                            id="add-user-button"
                            onClick={handleAddUser}
                            className="gap-2"
                        >
                            <Plus className="size-4" />
                            Add User
                        </Button>
                    </div>

                    {/* Tabs + Search */}
                    <Card>
                        <CardHeader className="pb-4">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                {/* Tabs */}
                                <div className="inline-flex rounded-lg bg-slate-100 p-1 dark:bg-zinc-800">
                                    <button
                                        id="tab-students"
                                        type="button"
                                        onClick={() => handleTabChange('students')}
                                        className={`inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all duration-200 cursor-pointer ${
                                            isStudentsTab
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-zinc-700 dark:text-white'
                                                : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white'
                                        }`}
                                    >
                                        <GraduationCap className="size-4" />
                                        Students
                                    </button>
                                    <button
                                        id="tab-employees"
                                        type="button"
                                        onClick={() => handleTabChange('employees')}
                                        className={`inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all duration-200 cursor-pointer ${
                                            !isStudentsTab
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-zinc-700 dark:text-white'
                                                : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white'
                                        }`}
                                    >
                                        <UserCog className="size-4" />
                                        Employees
                                    </button>
                                </div>

                                {/* Search */}
                                <div className="relative w-full sm:max-w-xs">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        id="search-users"
                                        type="text"
                                        placeholder="Search users..."
                                        value={searchValue}
                                        onChange={(e) => handleSearchChange(e.target.value)}
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent>
                            {/* Data Table */}
                            <div className="overflow-x-auto rounded-lg border border-slate-200 dark:border-zinc-700">
                                <table className="w-full text-sm" id="users-table">
                                    <thead>
                                        <tr className="border-b bg-slate-50 text-left text-slate-600 dark:bg-zinc-800/50 dark:text-zinc-400">
                                            {isStudentsTab ? (
                                                <>
                                                    <th className="px-4 py-3 font-medium">ID Number</th>
                                                    <th className="px-4 py-3 font-medium">RFID</th>
                                                    <th className="px-4 py-3 font-medium">Name</th>
                                                    <th className="px-4 py-3 font-medium">Level</th>
                                                    <th className="px-4 py-3 font-medium">Section</th>
                                                    <th className="px-4 py-3 font-medium">Guardian</th>
                                                    <th className="px-4 py-3 font-medium">Status</th>
                                                    <th className="px-4 py-3 font-medium text-center">Actions</th>
                                                </>
                                            ) : (
                                                <>
                                                    <th className="px-4 py-3 font-medium">Employee ID</th>
                                                    <th className="px-4 py-3 font-medium">RFID</th>
                                                    <th className="px-4 py-3 font-medium">Name</th>
                                                    <th className="px-4 py-3 font-medium">Role</th>
                                                    <th className="px-4 py-3 font-medium">Email</th>
                                                    <th className="px-4 py-3 font-medium">Status</th>
                                                    <th className="px-4 py-3 font-medium text-center">Actions</th>
                                                </>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={isStudentsTab ? 8 : 7}
                                                    className="py-12 text-center text-sm text-muted-foreground"
                                                >
                                                    {searchValue
                                                        ? 'No users found matching your search.'
                                                        : `No ${isStudentsTab ? 'students' : 'employees'} found. Click "Add User" to create one.`}
                                                </td>
                                            </tr>
                                        ) : (
                                            users.data.map((user) => (
                                                <tr
                                                    key={user.id}
                                                    className="border-b border-slate-100 transition-colors hover:bg-slate-50/50 last:border-0 dark:border-zinc-800 dark:hover:bg-zinc-800/30"
                                                >
                                                    {isStudentsTab ? (
                                                        <>
                                                            <td className="px-4 py-3 font-medium tabular-nums">
                                                                {user.student_detail?.id_number ?? '—'}
                                                            </td>
                                                            <td className="px-4 py-3 font-mono text-xs text-slate-500 dark:text-zinc-400">
                                                                {user.rfid}
                                                            </td>
                                                            <td className="px-4 py-3 font-medium">
                                                                {user.name}
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                {user.student_detail?.level ?? '—'}
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                {user.student_detail?.section ?? '—'}
                                                            </td>
                                                            <td className="px-4 py-3 text-slate-600 dark:text-zinc-400">
                                                                {user.student_detail?.guardian_name ?? '—'}
                                                            </td>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <td className="px-4 py-3 font-medium tabular-nums">
                                                                {user.employee_detail?.employee_id ?? '—'}
                                                            </td>
                                                            <td className="px-4 py-3 font-mono text-xs text-slate-500 dark:text-zinc-400">
                                                                {user.rfid}
                                                            </td>
                                                            <td className="px-4 py-3 font-medium">
                                                                {user.name}
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                {user.employee_detail?.employee_role ?? '—'}
                                                            </td>
                                                            <td className="px-4 py-3 text-slate-600 dark:text-zinc-400">
                                                                {user.email}
                                                            </td>
                                                        </>
                                                    )}
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={user.status ? 'default' : 'secondary'}
                                                            className={
                                                                user.status
                                                                    ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-100 dark:bg-zinc-800 dark:text-zinc-500'
                                                            }
                                                        >
                                                            {user.status ? 'Active' : 'Inactive'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center justify-center gap-1">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleViewUser(user)}
                                                                className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/20 dark:hover:text-blue-400 cursor-pointer"
                                                                title="View"
                                                            >
                                                                <Eye className="size-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleEditUser(user)}
                                                                className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20 dark:hover:text-amber-400 cursor-pointer"
                                                                title="Edit"
                                                            >
                                                                <Pencil className="size-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteUser(user)}
                                                                className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 cursor-pointer"
                                                                title="Delete"
                                                            >
                                                                <Trash2 className="size-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {users.last_page > 1 && (
                                <div className="mt-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
                                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-zinc-400">
                                        <span>
                                            Showing {users.from ?? 0}–{users.to ?? 0} of{' '}
                                            {users.total} results
                                        </span>
                                        <span className="mx-1 text-slate-300 dark:text-zinc-600">|</span>
                                        <span className="flex items-center gap-1.5">
                                            Per page:
                                            <input
                                                id="per-page-input"
                                                type="number"
                                                min={1}
                                                max={100}
                                                value={perPage}
                                                onChange={(e) =>
                                                    handlePerPageChange(
                                                        parseInt(e.target.value, 10) || 10,
                                                    )
                                                }
                                                className="h-7 w-16 rounded-md border border-slate-200 bg-white px-2 text-center text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-800"
                                            />
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={users.current_page <= 1}
                                            onClick={() => goToPage(users.current_page - 1)}
                                            className="gap-1"
                                        >
                                            <ChevronLeft className="size-4" />
                                            Prev
                                        </Button>
                                        {Array.from(
                                            { length: Math.min(users.last_page, 5) },
                                            (_, i) => {
                                                let page: number;

                                                if (users.last_page <= 5) {
                                                    page = i + 1;
                                                } else if (users.current_page <= 3) {
                                                    page = i + 1;
                                                } else if (
                                                    users.current_page >=
                                                    users.last_page - 2
                                                ) {
                                                    page = users.last_page - 4 + i;
                                                } else {
                                                    page = users.current_page - 2 + i;
                                                }

                                                return (
                                                    <Button
                                                        key={page}
                                                        variant={
                                                            page === users.current_page
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                        size="sm"
                                                        onClick={() => goToPage(page)}
                                                        className="min-w-8 tabular-nums"
                                                    >
                                                        {page}
                                                    </Button>
                                                );
                                            },
                                        )}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                users.current_page >= users.last_page
                                            }
                                            onClick={() => goToPage(users.current_page + 1)}
                                            className="gap-1"
                                        >
                                            Next
                                            <ChevronRight className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {/* Single-page footer info */}
                            {users.last_page <= 1 && users.total > 0 && (
                                <div className="mt-4 flex items-center justify-between text-sm text-slate-600 dark:text-zinc-400">
                                    <span>
                                        Showing {users.from ?? 0}–{users.to ?? 0} of{' '}
                                        {users.total} results
                                    </span>
                                    <span className="flex items-center gap-1.5">
                                        Per page:
                                        <input
                                            type="number"
                                            min={1}
                                            max={100}
                                            value={perPage}
                                            onChange={(e) =>
                                                handlePerPageChange(
                                                    parseInt(e.target.value, 10) || 10,
                                                )
                                            }
                                            className="h-7 w-16 rounded-md border border-slate-200 bg-white px-2 text-center text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-800"
                                        />
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Add / Edit Dialog */}
            <UserFormDialog
                isOpen={isFormOpen}
                onClose={() => {
                    setIsFormOpen(false);
                    setEditingUser(null);
                }}
                user={editingUser}
                tab={tab}
            />

            {/* View Dialog */}
            <UserViewDialog
                isOpen={isViewOpen}
                onClose={() => {
                    setIsViewOpen(false);
                    setViewingUser(null);
                }}
                user={viewingUser}
                tab={tab}
            />

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={isDeleteOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        setIsDeleteOpen(false);
                        setDeletingUser(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-semibold">
                                {deletingUser?.name}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setIsDeleteOpen(false);
                                setDeletingUser(null);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDelete}
                            disabled={deleteForm.processing}
                        >
                            {deleteForm.processing ? 'Deleting...' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
