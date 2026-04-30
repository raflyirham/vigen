import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-[#34cf23] bg-[#34cf23]/10 text-white focus:border-[#34cf23] focus:bg-[#34cf23]/15'
                    : 'border-transparent text-white/70 hover:border-white/20 hover:bg-white/[0.06] hover:text-white focus:border-white/20 focus:bg-white/[0.06] focus:text-white'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
