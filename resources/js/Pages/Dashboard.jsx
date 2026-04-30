import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

function StatusBadge({ status }) {
    const styles = {
        completed: 'border-[#34cf23]/30 bg-[#34cf23]/10 text-[#a8ff9f]',
        script_only: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        failed_with_fallback: 'border-red-300/30 bg-red-400/10 text-red-100',
        pending: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        processing: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
    };

    return (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-bold ${styles[status] ?? 'border-white/20 bg-white/[0.06] text-white/70'}`}>
            {status.replaceAll('_', ' ')}
        </span>
    );
}

export default function Dashboard({ stats, recentGenerations }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase text-[#34cf23]">
                            Studio overview
                        </p>
                        <h1 className="mt-1 text-3xl font-black text-white">
                            Dashboard
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-white/60">
                            Your saved generations, fallbacks, and next video brief live here.
                        </p>
                    </div>
                    <Link
                        href={route('generator.create')}
                        className="inline-flex min-h-11 items-center justify-center rounded-md bg-[#34cf23] px-4 text-sm font-black text-black transition hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                    >
                        Generate
                    </Link>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="grid gap-4 md:grid-cols-3">
                    {[
                        ['Total outputs', stats.total],
                        ['Video completed', stats.completed],
                        ['Fallback ready', stats.fallbacks],
                    ].map(([label, value]) => (
                        <article
                            key={label}
                            className="rounded-lg border border-white/10 bg-white/[0.07] p-5 shadow-xl shadow-black/20 backdrop-blur-xl"
                        >
                            <p className="text-sm font-semibold text-white/60">
                                {label}
                            </p>
                            <p className="mt-3 font-mono text-4xl font-black text-white">
                                {value}
                            </p>
                        </article>
                    ))}
                </section>

                <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="rounded-lg border border-white/10 bg-white/[0.07] p-6 shadow-xl shadow-black/20 backdrop-blur-xl">
                        <p className="text-sm font-bold uppercase text-[#34cf23]">
                            Next brief
                        </p>
                        <h2 className="mt-3 text-3xl font-black text-white">
                            Turn the next idea into a usable video plan.
                        </h2>
                        <p className="mt-3 max-w-2xl text-sm leading-7 text-white/60">
                            Pick a template, define the audience and tone, then export the script or let Grok attempt the video file when credentials are available.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Link
                                href={route('generator.create')}
                                className="inline-flex min-h-11 items-center rounded-md bg-[#34cf23] px-4 text-sm font-black text-black transition hover:bg-[#47e237]"
                            >
                                Open generator
                            </Link>
                            <Link
                                href={route('generations.index')}
                                className="inline-flex min-h-11 items-center rounded-md border border-white/20 bg-white/[0.06] px-4 text-sm font-semibold text-white transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10"
                            >
                                Browse history
                            </Link>
                        </div>
                    </div>

                    <aside className="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                        <h2 className="text-base font-black text-white">
                            Recent outputs
                        </h2>
                        <div className="mt-4 space-y-3">
                            {recentGenerations.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-white/20 p-4 text-sm leading-6 text-white/60">
                                    No saved generations yet.
                                </div>
                            ) : (
                                recentGenerations.map((generation) => (
                                    <Link
                                        key={generation.id}
                                        href={route('generations.show', generation.id)}
                                        className="block rounded-lg border border-white/10 bg-white/[0.045] p-3 transition hover:border-[#34cf23]/40 hover:bg-[#34cf23]/10"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="line-clamp-2 text-sm font-bold text-white">
                                                {generation.topic}
                                            </p>
                                            <StatusBadge status={generation.status} />
                                        </div>
                                        <p className="mt-2 text-xs text-white/50">
                                            {generation.video_type}
                                        </p>
                                    </Link>
                                ))
                            )}
                        </div>
                    </aside>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
