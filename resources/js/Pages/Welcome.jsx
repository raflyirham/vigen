import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

function SceneStrip() {
    const scenes = [
        ['00-06', 'Hook', 'Open with the audience pain point'],
        ['06-14', 'Build', 'Show the core promise in motion'],
        ['14-22', 'Proof', 'Bring keywords into the message'],
        ['22-30', 'Close', 'End with a sharp next step'],
    ];

    return (
        <div className="grid gap-3">
            {scenes.map(([time, label, text], index) => (
                <div
                    key={time}
                    className="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-lg border border-white/10 bg-white/[0.06] p-4 backdrop-blur"
                >
                    <div className="flex h-16 items-center justify-center rounded-md border border-[#34cf23]/30 bg-[#34cf23]/10 font-mono text-sm font-bold text-[#a8ff9f]">
                        {time}
                    </div>
                    <div>
                        <p className="text-sm font-bold text-white">
                            Scene {index + 1}: {label}
                        </p>
                        <p className="mt-1 text-sm leading-6 text-white/60">
                            {text}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Vigen" />
            <div className="relative min-h-screen overflow-hidden bg-[#050705] text-white">
                <div className="pointer-events-none fixed inset-0 opacity-70">
                    <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.035)_1px,transparent_1px)] bg-[size:56px_56px]" />
                    <div className="absolute inset-x-0 top-0 h-px bg-[#34cf23]/50" />
                </div>

                <div className="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
                    <header className="flex h-20 items-center justify-between">
                        <Link href="/" className="flex items-center gap-3">
                            <ApplicationLogo className="h-10 w-10 text-white" />
                            <span className="text-lg font-black">Vigen</span>
                        </Link>
                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex min-h-11 items-center rounded-md border border-white/20 bg-white/[0.06] px-4 text-sm font-semibold text-white transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="hidden min-h-11 items-center rounded-md px-4 text-sm font-semibold text-white/70 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#34cf23] sm:inline-flex"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="inline-flex min-h-11 items-center rounded-md bg-[#34cf23] px-4 text-sm font-bold text-black transition hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </nav>
                    </header>

                    <main className="grid flex-1 items-center gap-10 pb-10 pt-6 lg:grid-cols-[minmax(0,0.92fr)_minmax(420px,1fr)]">
                        <section className="pb-8">
                            <p className="text-sm font-bold uppercase text-[#34cf23]">
                                AI video studio
                            </p>
                            <h1 className="mt-5 max-w-3xl text-5xl font-black leading-[1.02] text-white sm:text-6xl lg:text-7xl">
                                Generate video content from a clean creative brief.
                            </h1>
                            <p className="mt-6 max-w-2xl text-base leading-8 text-white/60 sm:text-lg">
                                Turn topics, keywords, audience, tone, and duration into saved scripts, scene-by-scene storyboards, and Grok video output when credentials are configured.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link
                                    href={auth.user ? route('generator.create') : route('register')}
                                    className="inline-flex min-h-12 items-center rounded-md bg-[#34cf23] px-5 text-sm font-black text-black shadow-[0_0_24px_rgba(52,207,35,0.22)] transition hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                >
                                    Start generating
                                </Link>
                                <Link
                                    href={auth.user ? route('generations.index') : route('login')}
                                    className="inline-flex min-h-12 items-center rounded-md border border-white/20 bg-white/[0.06] px-5 text-sm font-semibold text-white transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                >
                                    View history
                                </Link>
                            </div>
                        </section>

                        <section className="rounded-lg border border-white/10 bg-white/[0.07] p-4 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-5">
                            <div className="flex items-center justify-between border-b border-white/10 pb-4">
                                <div>
                                    <p className="text-xs font-bold uppercase text-[#34cf23]">
                                        Preview
                                    </p>
                                    <h2 className="mt-1 text-xl font-black text-white">
                                        Social reel storyboard
                                    </h2>
                                </div>
                                <span className="rounded-full border border-[#34cf23]/30 bg-[#34cf23]/10 px-3 py-1 text-xs font-bold text-[#a8ff9f]">
                                    30s
                                </span>
                            </div>
                            <div className="mt-5 aspect-video rounded-lg border border-white/10 bg-[#080b08] p-4">
                                <div className="flex h-full flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="rounded-md bg-[#34cf23] px-2 py-1 text-xs font-black text-black">
                                            VIGEN
                                        </span>
                                        <span className="font-mono text-xs text-white/50">
                                            grok-imagine-1.0-video
                                        </span>
                                    </div>
                                    <div>
                                        <p className="text-3xl font-black text-white">
                                            Launch workflow in 30 seconds
                                        </p>
                                        <div className="mt-4 h-2 rounded-full bg-white/10">
                                            <div className="h-2 w-2/3 rounded-full bg-[#34cf23]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="mt-4">
                                <SceneStrip />
                            </div>
                        </section>
                    </main>

                    <section className="grid gap-3 border-t border-white/10 py-6 sm:grid-cols-3">
                        {[
                            ['Authenticated', 'Private histories stay scoped to each user.'],
                            ['Fallback ready', 'Script and storyboard save even when video fails.'],
                            ['Exportable', 'Copy text, export .txt, or download available videos.'],
                        ].map(([title, text]) => (
                            <article
                                key={title}
                                className="rounded-lg border border-white/10 bg-white/[0.045] p-4"
                            >
                                <h3 className="text-sm font-bold text-white">
                                    {title}
                                </h3>
                                <p className="mt-2 text-sm leading-6 text-white/60">
                                    {text}
                                </p>
                            </article>
                        ))}
                    </section>
                </div>
            </div>
        </>
    );
}
