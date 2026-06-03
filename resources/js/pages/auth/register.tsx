import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const { palette, rgb } = useUiTheme();

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="student_id">Student ID</Label>
                                <Input
                                    id="student_id"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    name="student_id"
                                    placeholder="Enter Student ID"
                                />
                                <InputError message={errors.student_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="rfid">RFID Tag</Label>
                                <Input
                                    id="rfid"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    name="rfid"
                                    placeholder="Scan or Enter RFID"
                                />
                                <InputError message={errors.rfid} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="first_name">First Name</Label>
                                <Input
                                    id="first_name"
                                    type="text"
                                    required
                                    tabIndex={3}
                                    name="first_name"
                                    placeholder="First Name"
                                />
                                <InputError message={errors.first_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="middle_name">Middle Name</Label>
                                <Input
                                    id="middle_name"
                                    type="text"
                                    tabIndex={4}
                                    name="middle_name"
                                    placeholder="Middle Name (Optional)"
                                />
                                <InputError message={errors.middle_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input
                                    id="last_name"
                                    type="text"
                                    required
                                    tabIndex={5}
                                    name="last_name"
                                    placeholder="Last Name"
                                />
                                <InputError message={errors.last_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={6}
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="guardian_name">Guardian Name</Label>
                                <Input
                                    id="guardian_name"
                                    type="text"
                                    required
                                    tabIndex={7}
                                    name="guardian_name"
                                    placeholder="Guardian Name"
                                />
                                <InputError message={errors.guardian_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="guardian_contact_number">Guardian Contact #</Label>
                                <Input
                                    id="guardian_contact_number"
                                    type="text"
                                    required
                                    tabIndex={8}
                                    name="guardian_contact_number"
                                    placeholder="Contact Number"
                                />
                                <InputError message={errors.guardian_contact_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={9}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm Password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={10}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full border-0 text-white"
                            style={{
                                backgroundColor: rgb(palette.primary['500']),
                                boxShadow: `0 18px 32px ${rgb(palette.primary['700'], 0.22)}`,
                            }}
                            tabIndex={11}
                            data-test="register-user-button"
                        >
                            {processing && <Spinner />}
                            Create account
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login()}
                                tabIndex={12}
                                style={{
                                    color: rgb(palette.primary['700']),
                                }}
                            >
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
