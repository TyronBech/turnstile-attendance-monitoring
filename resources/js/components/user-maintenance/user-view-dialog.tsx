import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { UserTab, UserWithDetails } from '@/types';

type UserViewDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    user: UserWithDetails | null;
    tab: UserTab;
};

/**
 * Renders a label + value pair in the view dialog.
 */
function DetailRow({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs font-medium text-slate-500 uppercase tracking-wide dark:text-zinc-400">
                {label}
            </dt>
            <dd className="text-sm font-medium text-slate-900 dark:text-white">
                {value || <span className="text-slate-400 dark:text-zinc-600">—</span>}
            </dd>
        </div>
    );
}

export function UserViewDialog({
    isOpen,
    onClose,
    user,
    tab,
}: UserViewDialogProps) {
    if (!user) {
return null;
}

    const isStudentTab = tab === 'students';

    return (
        <Dialog
            open={isOpen}
            onOpenChange={(open) => {
                if (!open) {
onClose();
}
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>User Details</DialogTitle>
                </DialogHeader>

                <div className="space-y-6">
                    {/* General Information */}
                    <div>
                        <h3 className="mb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-zinc-400">
                            General Information
                        </h3>
                        <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
                            <DetailRow label="First Name" value={user.first_name} />
                            <DetailRow label="Last Name" value={user.last_name} />
                            <DetailRow
                                label="Middle Name"
                                value={user.middle_name}
                            />
                            <DetailRow label="RFID" value={
                                <span className="font-mono text-xs">{user.rfid}</span>
                            } />
                            <DetailRow label="Email" value={user.email} />
                            <DetailRow
                                label="Status"
                                value={
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
                                }
                            />
                        </dl>
                    </div>

                    {/* Divider */}
                    <div className="border-t border-slate-200 dark:border-zinc-700" />

                    {/* Detail-specific Information */}
                    {isStudentTab && user.student_detail && (
                        <div>
                            <h3 className="mb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-zinc-400">
                                Student Details
                            </h3>
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
                                <DetailRow
                                    label="ID Number"
                                    value={user.student_detail.id_number}
                                />
                                <DetailRow
                                    label="Level / Grade"
                                    value={user.student_detail.level}
                                />
                                <DetailRow
                                    label="Section"
                                    value={user.student_detail.section}
                                />
                                <DetailRow
                                    label="Guardian Name"
                                    value={user.student_detail.guardian_name}
                                />
                                <DetailRow
                                    label="Guardian Contact"
                                    value={user.student_detail.guardian_contact_number}
                                />
                            </dl>
                        </div>
                    )}

                    {!isStudentTab && user.employee_detail && (
                        <div>
                            <h3 className="mb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-zinc-400">
                                Employee Details
                            </h3>
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
                                <DetailRow
                                    label="Employee ID"
                                    value={user.employee_detail.employee_id}
                                />
                                <DetailRow
                                    label="Role"
                                    value={user.employee_detail.employee_role}
                                />
                            </dl>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
