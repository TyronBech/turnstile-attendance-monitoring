import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your personal and guardian information"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="student_id">Student ID</Label>
                                    <Input
                                        id="student_id"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.student_id}
                                        name="student_id"
                                        required
                                        placeholder="Enter Student ID"
                                    />
                                    <InputError message={errors.student_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="rfid">RFID Tag</Label>
                                    <Input
                                        id="rfid"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.rfid}
                                        name="rfid"
                                        required
                                        placeholder="Scan or Enter RFID"
                                    />
                                    <InputError message={errors.rfid} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">First Name</Label>
                                    <Input
                                        id="first_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.first_name}
                                        name="first_name"
                                        required
                                        placeholder="First Name"
                                    />
                                    <InputError message={errors.first_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="middle_name">Middle Name</Label>
                                    <Input
                                        id="middle_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.middle_name || ''}
                                        name="middle_name"
                                        placeholder="Middle Name (Optional)"
                                    />
                                    <InputError message={errors.middle_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">Last Name</Label>
                                    <Input
                                        id="last_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.last_name}
                                        name="last_name"
                                        required
                                        placeholder="Last Name"
                                    />
                                    <InputError message={errors.last_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_name">Guardian Name</Label>
                                    <Input
                                        id="guardian_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.guardian_name}
                                        name="guardian_name"
                                        required
                                        placeholder="Guardian Name"
                                    />
                                    <InputError message={errors.guardian_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_contact_number">Guardian Contact #</Label>
                                    <Input
                                        id="guardian_contact_number"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.guardian_contact_number}
                                        name="guardian_contact_number"
                                        required
                                        placeholder="Contact Number"
                                    />
                                    <InputError message={errors.guardian_contact_number} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
