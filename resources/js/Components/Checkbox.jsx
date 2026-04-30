export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-white/20 bg-white/[0.08] text-[#34cf23] shadow-sm focus:ring-[#34cf23] focus:ring-offset-black ' +
                className
            }
        />
    );
}
