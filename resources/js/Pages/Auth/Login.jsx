import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 rounded-md border border-[#34cf23]/30 bg-[#34cf23]/10 px-4 py-3 text-sm font-medium text-[#a8ff9f]">
                    {status}
                </div>
            )}

            <div className="mb-6">
                <p className="text-sm font-semibold text-[#34cf23]">
                    Welcome back
                </p>
                <h1 className="mt-2 text-3xl font-black text-white">
                    Log in to your studio
                </h1>
                <p className="mt-2 text-sm leading-6 text-white/60">
                    Continue generating briefs, storyboards, and video-ready exports.
                </p>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-white/60">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-4">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-md text-sm font-medium text-white/60 underline hover:text-white focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                        >
                            Forgot your password?
                        </Link>
                    )}

                    <PrimaryButton disabled={processing}>
                        Log in
                    </PrimaryButton>
                </div>

                <p className="mt-6 text-sm text-white/60">
                    New here?{' '}
                    <Link
                        href={route('register')}
                        className="font-semibold text-[#34cf23] hover:text-[#7dff72]"
                    >
                        Create an account
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
