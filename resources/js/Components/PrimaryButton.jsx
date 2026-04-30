export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex min-h-11 items-center justify-center rounded-md border border-[#34cf23]/70 bg-[#34cf23] px-4 py-2 text-sm font-bold text-black shadow-[0_0_24px_rgba(52,207,35,0.22)] transition duration-200 ease-out hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23] focus:ring-offset-2 focus:ring-offset-black active:scale-[0.99] disabled:cursor-not-allowed ${
                    disabled && 'opacity-50'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
