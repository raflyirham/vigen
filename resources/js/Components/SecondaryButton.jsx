export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex min-h-11 items-center justify-center rounded-md border border-white/20 bg-white/[0.06] px-4 py-2 text-sm font-semibold text-white shadow-sm backdrop-blur transition duration-200 ease-out hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23] focus:ring-offset-2 focus:ring-offset-black disabled:cursor-not-allowed disabled:opacity-50 ${
                    disabled && 'opacity-50'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
