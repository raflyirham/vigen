export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <svg
            {...props}
            className={className}
            viewBox="0 0 64 64"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect
                x="5"
                y="5"
                width="54"
                height="54"
                rx="14"
                fill="#34cf23"
                fillOpacity="0.12"
                stroke="#34cf23"
                strokeWidth="2"
            />
            <path
                d="M21 18L32 46L43 18"
                stroke="#34cf23"
                strokeWidth="5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M18 32H46"
                stroke="currentColor"
                strokeWidth="3"
                strokeLinecap="round"
                opacity="0.9"
            />
        </svg>
    );
}
