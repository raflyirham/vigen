import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-semibold leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                (active
                    ? 'border-[#34cf23] text-white focus:border-[#34cf23]'
                    : 'border-transparent text-white/60 hover:border-white/20 hover:text-white focus:border-white/20 focus:text-white') +
                className
            }
        >
            {children}
        </Link>
    );
}
