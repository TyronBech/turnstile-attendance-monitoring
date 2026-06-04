import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { UserTab, UserWithDetails } from '@/types';

type UserFormDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    user: UserWithDetails | null;
    tab: UserTab;
};

/**
 * Renders a red asterisk for required fields.
 */
function RequiredMark() {
    return <span className="ml-0.5 text-red-500">*</span>;
}

export function UserFormDialog({
    isOpen,
    onClose,
    user,
    tab,
}: UserFormDialogProps) {
    const isEditing = user !== null;
    const isStudentTab = tab === 'students';

    const form = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        rfid: '',
        email: '',
        status: true,
        user_type: isStudentTab ? 'student' : 'employee',
        // Student fields
        id_number: '',
        level: '',
        section: '',
        guardian_name: '',
        guardian_contact_number: '',
        // Employee fields
        employee_id: '',
        employee_role: '',
    });

    // Populate form when editing or reset when adding
    useEffect(() => {
        if (!isOpen) {
return;
}

        if (isEditing && user) {
            form.setData({
                first_name: user.first_name ?? '',
                middle_name: user.middle_name ?? '',
                last_name: user.last_name ?? '',
                rfid: user.rfid ?? '',
                email: user.email ?? '',
                status: user.status ?? true,
                user_type: isStudentTab ? 'student' : 'employee',
                id_number: user.student_detail?.id_number ?? '',
                level: user.student_detail?.level ?? '',
                section: user.student_detail?.section ?? '',
                guardian_name: user.student_detail?.guardian_name ?? '',
                guardian_contact_number:
                    user.student_detail?.guardian_contact_number ?? '',
                employee_id: user.employee_detail?.employee_id ?? '',
                employee_role: user.employee_detail?.employee_role ?? '',
            });
        } else {
            form.reset();
            form.clearErrors();
            form.setData('user_type', isStudentTab ? 'student' : 'employee');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isOpen, user, isStudentTab]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && user) {
            form.put(`/user-maintenance/users/${user.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('User updated successfully.');
                    onClose();
                },
                onError: () => {
                    toast.error('Failed to update user. Please check the form errors.');
                },
            });
        } else {
            form.post('/user-maintenance/users', {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('User created successfully.');
                    onClose();
                },
                onError: () => {
                    toast.error('Failed to create user. Please check the form errors.');
                },
            });
        }
    };

    return (
        <Dialog
            open={isOpen}
            onOpenChange={(open) => {
                if (!open) {
onClose();
}
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit User' : 'Add User'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? `Update the details for ${user?.name}.`
                            : `Add a new ${isStudentTab ? 'student' : 'employee'} record.`}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-5">
                    {/* Common Fields */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {/* First Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-first-name">
                                First Name
                                <RequiredMark />
                            </Label>
                            <Input
                                id="form-first-name"
                                value={form.data.first_name}
                                onChange={(e) =>
                                    form.setData('first_name', e.target.value)
                                }
                                placeholder="e.g. Juan"
                            />
                            <InputError message={form.errors.first_name} />
                        </div>

                        {/* Middle Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-middle-name">
                                Middle Name
                            </Label>
                            <Input
                                id="form-middle-name"
                                value={form.data.middle_name}
                                onChange={(e) =>
                                    form.setData('middle_name', e.target.value)
                                }
                                placeholder="e.g. Santos"
                            />
                            <InputError message={form.errors.middle_name} />
                        </div>

                        {/* Last Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-last-name">
                                Last Name
                                <RequiredMark />
                            </Label>
                            <Input
                                id="form-last-name"
                                value={form.data.last_name}
                                onChange={(e) =>
                                    form.setData('last_name', e.target.value)
                                }
                                placeholder="e.g. Dela Cruz"
                            />
                            <InputError message={form.errors.last_name} />
                        </div>

                        {/* RFID */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-rfid">
                                RFID
                                <RequiredMark />
                            </Label>
                            <Input
                                id="form-rfid"
                                value={form.data.rfid}
                                onChange={(e) =>
                                    form.setData('rfid', e.target.value)
                                }
                                placeholder="e.g. A1B2C3D4"
                            />
                            <InputError message={form.errors.rfid} />
                        </div>

                        {/* Email */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-email">
                                Email
                                <RequiredMark />
                            </Label>
                            <Input
                                id="form-email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                placeholder="e.g. juan@school.edu"
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        {/* Status */}
                        <div className="space-y-1.5">
                            <Label htmlFor="form-status">
                                Status
                                <RequiredMark />
                            </Label>
                            <Select
                                value={form.data.status ? 'active' : 'inactive'}
                                onValueChange={(value) =>
                                    form.setData('status', value === 'active')
                                }
                            >
                                <SelectTrigger id="form-status" className="w-full">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.status} />
                        </div>
                    </div>

                    {/* Separator */}
                    <div className="border-t border-slate-200 dark:border-zinc-700" />

                    {/* Tab-specific fields */}
                    {isStudentTab ? (
                        <>
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-zinc-300">
                                Student Details
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {/* ID Number */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-id-number">
                                        ID Number
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-id-number"
                                        value={form.data.id_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'id_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. 2024-00001"
                                    />
                                    <InputError
                                        message={form.errors.id_number}
                                    />
                                </div>

                                {/* Level */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-level">
                                        Level / Grade
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-level"
                                        value={form.data.level}
                                        onChange={(e) =>
                                            form.setData(
                                                'level',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Grade 10"
                                    />
                                    <InputError message={form.errors.level} />
                                </div>

                                {/* Section */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-section">
                                        Section
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-section"
                                        value={form.data.section}
                                        onChange={(e) =>
                                            form.setData(
                                                'section',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Section A"
                                    />
                                    <InputError
                                        message={form.errors.section}
                                    />
                                </div>

                                {/* Guardian Name */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-guardian-name">
                                        Guardian Name
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-guardian-name"
                                        value={form.data.guardian_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'guardian_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Maria Dela Cruz"
                                    />
                                    <InputError
                                        message={form.errors.guardian_name}
                                    />
                                </div>

                                {/* Guardian Contact */}
                                <div className="space-y-1.5 sm:col-span-2">
                                    <Label htmlFor="form-guardian-contact">
                                        Guardian Contact Number
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-guardian-contact"
                                        value={
                                            form.data.guardian_contact_number
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'guardian_contact_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. 09171234567"
                                    />
                                    <InputError
                                        message={
                                            form.errors.guardian_contact_number
                                        }
                                    />
                                </div>
                            </div>
                        </>
                    ) : (
                        <>
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-zinc-300">
                                Employee Details
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {/* Employee ID */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-employee-id">
                                        Employee ID
                                        <RequiredMark />
                                    </Label>
                                    <Input
                                        id="form-employee-id"
                                        value={form.data.employee_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'employee_id',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. EMP-001"
                                    />
                                    <InputError
                                        message={form.errors.employee_id}
                                    />
                                </div>

                                {/* Employee Role */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="form-employee-role">
                                        Role
                                    </Label>
                                    <Input
                                        id="form-employee-role"
                                        value={form.data.employee_role}
                                        onChange={(e) =>
                                            form.setData(
                                                'employee_role',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Teacher"
                                    />
                                    <InputError
                                        message={form.errors.employee_role}
                                    />
                                </div>
                            </div>
                        </>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? isEditing
                                    ? 'Saving...'
                                    : 'Creating...'
                                : isEditing
                                  ? 'Save Changes'
                                  : 'Create User'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
