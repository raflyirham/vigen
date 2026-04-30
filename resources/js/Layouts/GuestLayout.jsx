import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen overflow-hidden bg-[#050705] text-white">
            <div className="pointer-events-none fixed inset-0 opacity-70">
                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.035)_1px,transparent_1px)] bg-[size:56px_56px]" />
                <div className="absolute inset-x-0 top-0 h-px bg-[#34cf23]/50" />
            </div>

            <div className="relative mx-auto grid w-full max-w-6xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(360px,440px)] lg:px-8">
                <section className="hidden lg:block">
                    <Link href="/" className="inline-flex items-center gap-3">
                        <ApplicationLogo className="h-10 w-10 text-white" />
                        <span className="text-lg font-black">Vigen</span>
                    </Link>
                    <h1 className="mt-10 max-w-xl text-5xl font-black leading-tight text-white">
                        Script, storyboard, and video direction in one focused studio.
                    </h1>
                    <p className="mt-5 max-w-lg text-base leading-7 text-white/60">
                        Build marketing clips, educational explainers, and social reels from a structured creative brief with a Grok video path when credentials are ready.
                    </p>
                    <div className="mt-8 grid max-w-xl grid-cols-3 gap-3">
                        {['Brief', 'Scenes', 'Export'].map((item) => (
                            <div
                                key={item}
                                className="rounded-lg border border-white/10 bg-white/[0.05] p-4 backdrop-blur"
                            >
                                <div className="h-1 w-8 rounded-full bg-[#34cf23]" />
                                <p className="mt-3 text-sm font-semibold text-white">
                                    {item}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="w-full">
                    <div className="mb-8 flex items-center justify-center gap-3 lg:hidden">
                        <ApplicationLogo className="h-10 w-10 text-white" />
                        <span className="text-lg font-black">Vigen</span>
                    </div>
                    <div className="rounded-lg border border-white/10 bg-white/[0.07] p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
                        {children}
                    </div>
                </section>
            </div>
        </div>
    );
}
